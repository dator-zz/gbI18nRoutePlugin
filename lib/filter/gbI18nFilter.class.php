<?php
/*
 * This file is part of the gbI18nRoute Plugin.
 * (c) 2010 Philippe Gamache <philippe.gamache@symfony-project.com> & Jean-Philippe Blais <jphblais@zinfo.ca>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Description of gbI18nFilterclass
 *
 *  
 * Base on code from : jānis kiršteins http://janiskirsteins.org/2010/04/09/dealing-with-unspecified-culture-in-a-multi-language-symfony-1-4-application/
 *
 * @author Philippe Gamache <philippe.gamache@symfony-project.com>
 * @author Jean-Philippe Blais <jphblais@zinfo.ca>
 */
class gbI18nFilter extends sfFilter
{
  protected function isFirstRequest($set = null)
  {
    if (!is_null($set))
    {
      $this->context->getUser()->setAttribute('isFirstRequest', $set);
    }

    return $this->context->getUser()->getAttribute('isFirstRequest', true);
  }

  protected function getCookieName()
  {
    return sfConfig::get('app_culture_cookie_name', 'culture_cookie');
  }

  protected function getCookieCulture()
  {
    return $this->context->getRequest()->getCookie($this->getCookieName());
  }

  protected function setCookieCulture($culture)
  {
    $cookie_name = $this->getCookieName();
    // Cookie length = Use sfGuardPlugin value, if sfGuardPlugin ain't use, cookie length = 15 days
    $cookie_length = sfConfig::get('app_sf_guard_plugin_remember_key_expiration_age',
        1296000);

    $this->context->getResponse()->setCookie($cookie_name, $culture, time() + $cookie_length);
  }

  protected function setCulture($culture)
  {
    $this->context->getUser()->setCulture($culture);
    $this->setCookieCulture($culture);

    if (in_array('sfPropelPlugin', $this->context->getConfiguration->getPlugins()))
    {
      sfPropel::setDefaultCulture($culture);
    }
  }

  public function execute($filterChain)
  {
    $sf_culture = $this->context->getRequest()->getParameter('sf_culture', null);
    $cookie_culture = $this->getCookieCulture();

    if (is_null($sf_culture))
    {
      if ($this->isFirstRequest())
      {
        $culture = empty($cookie_culture)
            ? $this->context->getRequest()->getPreferredCulture(
                sfConfig::get('app_culture_supported_list',
                array('en')
              ))
            : $cookie_culture;

        $this->setCulture($culture);
        $this->isFirstRequest(false);
      }

      $this->context->getController()->redirect('@homepage');
    }
    else
    {
      if ($sf_culture != $cookie_culture)
      {
        $this->setCookieCulture($sf_culture);
      }
    }
  }
}
