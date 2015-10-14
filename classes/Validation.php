<?php

class Validation
{
  public static function checkPhone($value)
  {
    // simple phone check to prevent users from entering clearly invalid phone numbers
    $phoneDigitsOnly = preg_replace('/[^0-9]/', '', $value);
    $occuringDigits = (strlen($phoneDigitsOnly) >= 1) ? array_unique(str_split($phoneDigitsOnly)) : array();
    
    return strlen($phoneDigitsOnly) >= 8 && count($occuringDigits) >= 2;
  }

  public static function checkEmail($value)
  {
    return preg_match("/^[^, ]+@[^, ]+\.[^, ]+$/", $value) == 1;
  }
}
