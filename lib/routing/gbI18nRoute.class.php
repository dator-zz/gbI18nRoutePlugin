<?php

/*
 * This file is part of the gbI18nRoute Plugin.
 * (c) 2010 Philippe Gamache <philippe.gamache@symfony-project.com> & Jean-Philippe Blais <jphblais@zinfo.ca>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * gbI18nRoute
 *
 * Based on original code by Fabien Potencier
 *
 * @author Philippe Gamache <philippe.gamache@symfony-project.com>
 * @author Jean-Philippe Blais <jphblais@zinfo.ca>
 */

class gbI18nRoute extends sfRoute
{
    protected
    $full_pattern      = array(),
    $full_regex        = array(),
    $full_tokens       = array(),
    $full_staticPrefix = array();

  /**
   * Constructor.
   *
   * Available options:
   *
   *  * variable_prefixes:                An array of characters that starts a variable name (: by default)
   *  * segment_separators:               An array of allowed characters for segment separators (/ and . by default)
   *  * variable_regex:                   A regex that match a valid variable name ([\w\d_]+ by default)
   *  * generate_shortest_url:            Whether to generate the shortest URL possible (true by default)
   *  * extra_parameters_as_query_string: Whether to generate extra parameters as a query string
   *
   * @param string $pattern       The pattern to match
   * @param array  $defaults      An array of default parameter values
   * @param array  $requirements  An array of requirements for parameters (regexes)
   * @param array  $options       An array of options
   */
  public function __construct($pattern, array $defaults = array(), array $requirements = array(), array $options = array())
  {
    parent::__construct('tmppattern', $defaults, $requirements, $options);
    if (is_array($pattern))
    {
      foreach($pattern as $key => $value)
      {
        $pattern[$key] = trim($value);
      }
    }
    else
    {
      $old_pattern = trim($pattern);
      $pattern = array();
      $culture = sfConfig::get('app_culture_supported_list', array('en'));

      foreach($culture as $key)
      {
        $pattern[$key] = $old_pattern;
      }
    }
    $this->pattern      = $pattern;
    $this->full_pattern = $pattern;
  }

  /**
   * Returns an array of parameters if the URL matches this route, false otherwise.
   *
   * @param  string  $url     The URL
   * @param  array   $context The context
   *
   * @return array   An array of parameters
   */
  public function matchesUrl($url, $context = array())
  {
    if (!$this->compiled)
    {
      $this->compile();
    }

    $matches = false;

    $culture = sfConfig::get('app_culture_supported_list', array('en'));

    foreach($culture as $key)
    {
      $this->pattern        = $this->full_pattern[$key];
      $this->tokens         = $this->full_tokens[$key];
      $this->regex          = $this->full_regex[$key];
      $this->staticPrefix   = $this->full_staticPrefix[$key];

      $matches = parent::matchesUrl($url, $context);
      if ($matches !== false)
      {
        $matches['sf_culture'] = $key;
        return $matches;
      }
    }
    return false;
  }
  
  /**
   * Generates a URL from the given parameters.
   *
   * @param   mixed     $params     The parameter values
   * @param   array     $context    The context
   * @param   Boolean   $absolute   Whether to generate an absolute URL
   *
   * @return  string    The generated URL
   */
  public function generate($params, $context = array(), $absolute = false)
  {

    if (!$this->compiled)
    {
      $this->compile();
    }
    $this->pattern      = $this->full_pattern[sfContext::getInstance()->getUser()->getCulture()];
    $this->tokens       = $this->full_tokens[sfContext::getInstance()->getUser()->getCulture()];
    $this->regex        = $this->full_regex[sfContext::getInstance()->getUser()->getCulture()];
    $this->staticPrefix = $this->full_staticPrefix[sfContext::getInstance()->getUser()->getCulture()];

    return parent::generate($params, $context, $absolute);
  }

  public function compile()
  {
    if ($this->compiled)
    {
      return;
    }

    $this->full_tokens = array();
    $this->full_regex = array();
    $this->full_staticPrefix = array();
    
    $culture = sfConfig::get('app_culture_supported_list', array('en'));

    foreach($culture as $key)
    {
      $this->compiled = false;
      $this->pattern = $this->full_pattern[$key];
      parent::compile();
      $this->full_tokens[$key] = $this->tokens;
      $this->full_regex[$key] = $this->regex;
      $this->full_staticPrefix[$key] = $this->staticPrefix;
    }

    $this->compiled = true;
  }

  public function serialize()
  {
    // always serialize compiled routes
    $this->compile();
    // sfPatternRouting will always re-set defaultParameters, so no need to serialize them
    return serialize(array($this->tokens, $this->defaultOptions, $this->options, $this->pattern, $this->staticPrefix, $this->regex, $this->variables, $this->defaults, $this->requirements, $this->suffix, $this->full_pattern, $this->full_tokens, $this->full_regex, $this->full_staticPrefix));
  }

  public function unserialize($data)
  {
    list($this->tokens, $this->defaultOptions, $this->options, $this->pattern, $this->staticPrefix, $this->regex, $this->variables, $this->defaults, $this->requirements, $this->suffix, $this->full_pattern, $this->full_tokens, $this->full_regex, $this->full_staticPrefix) = unserialize($data);
    $this->compiled = true;
  }
}
