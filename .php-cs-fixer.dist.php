<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Fixer\FunctionNotation\NativeFunctionInvocationFixer;

$config = new Config();

return $config
  ->setRiskyAllowed(true)
  ->setRules([
    '@PSR12' => true,
    '@PhpCsFixer' => true,
    'simplified_null_return' => true,
    'void_return' => true,
    'native_constant_invocation' => true,
    'native_function_invocation' => [
      'include' => [NativeFunctionInvocationFixer::SET_ALL],
      'scope' => 'namespaced',
      'strict' => true,
    ],
    'no_superfluous_phpdoc_tags' => true,
    'phpdoc_no_package' => false,
    'phpdoc_to_comment' => [
      'allow_before_return_statement' => true,
    ],
    'general_phpdoc_annotation_remove' => [
      'annotations' => ['author'],
      'case_sensitive' => false,
    ],
    'phpdoc_separation' => false,
    'phpdoc_align' => [
      'align' => 'vertical',
    ],
  ])
  ->setFinder(
    Finder::create()
      ->exclude('vendor')
      ->in(__DIR__ . '/src')
      ->in(__DIR__ . '/tests')
  )
;
