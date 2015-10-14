<?php

class Handler_Contact extends Handler
{
  protected $captchas;

  public function __construct($arguments)
  {
    parent::__construct($arguments);

    $this->captchas = array(
      'Hoeveel velden heeft een schaakbord?' => 64,
      'Welk schaakstuk begint op veld e1?'   => 'koning',
      'Welk schaakstuk begint op veld d1?'   => 'dame',
      'Welk schaakstuk begint op veld a1?'   => 'toren',
      'Welk schaakstuk begint op veld c1?'   => 'loper',
      'Welk schaakstuk begint op veld b1?'   => 'paard',
      'Welk schaakstuk begint op veld a2?'   => 'pion'
    );
  }

  public function handleRequest()
  {
    if (array_key_exists('send', $_POST))
      $this->handleSendRequest();

    $index = mt_rand(0, count($this->captchas) - 1);
    $captchaQuestions = array_keys($this->captchas);
    $captchaQuestion  = $captchaQuestions[$index];
    $captchaAnswer    = $this->captchas[$captchaQuestion];

    $template = Util::getTemplate('contact');
    $variables = array(
      'docroot'          => APPLICATION_DOCROOT,
      'captcha-question' => $captchaQuestion,
      'captcha-answer'   => $captchaAnswer,
      'name'             => array_key_exists('name', $_POST)    ? trim($_POST['name']) : '',
      'phone'            => array_key_exists('phone', $_POST)   ? trim($_POST['phone']) : '',
      'email'            => array_key_exists('email', $_POST)   ? trim($_POST['email']) : '',
      'subject'          => array_key_exists('subject', $_POST) ? trim($_POST['subject']) : '',
      'message'          => array_key_exists('message', $_POST) ? trim($_POST['message']) : '',
    );

    $body = Util::formatString($template, $variables);

    echo $this->formatTemplate(array('body' => $body));
  }

  public function handleSendRequest()
  {
    $errors = array();

    $name   = array_key_exists('name', $_POST)  ? trim($_POST['name'])  : null;
    $phone  = array_key_exists('phone', $_POST) ? trim($_POST['phone']) : null;
    $email  = array_key_exists('email', $_POST) ? trim($_POST['email']) : null;

    if (strlen($name) == 0)
      $errors[] = '- uw naam ontbreekt';

    if (strlen($phone) >= 1 && !Validation::checkPhone($phone))
      $errors[] = '- uw (mobiele) nummer is ongeldig';

    if (strlen($email) >= 1 && !Validation::checkEmail($email))
      $errors[] = '- uw e-mailadres is ongeldig';

    if (strlen($phone) == 0 && strlen($email) == 0)
      $errors[] = '- uw (mobiele) nummer en/of e-mailadres ontbreekt';

    $to = null;
    if (array_key_exists('to', $_POST))
    {
      switch ($_POST['to'])
      {
        case 'voorzitter':             { if (defined('EMAIL_VOORZITTER'))             $to = EMAIL_VOORZITTER;             break; }
        case 'secretaris':             { if (defined('EMAIL_SECRETARIS'))             $to = EMAIL_SECRETARIS;             break; }
        case 'penningmeester':         { if (defined('EMAIL_PENNINGMEESTER'))         $to = EMAIL_PENNINGMEESTER;         break; }
        case 'intern_wedstrijdleider': { if (defined('EMAIL_INTERN_WEDSTRIJDLEIDER')) $to = EMAIL_INTERN_WEDSTRIJDLEIDER; break; }
        case 'extern_wedstrijdleider': { if (defined('EMAIL_EXTERN_WEDSTRIJDLEIDER')) $to = EMAIL_EXTERN_WEDSTRIJDLEIDER; break; }
        case 'webmaster':              { if (defined('EMAIL_WEBMASTER'))              $to = EMAIL_WEBMASTER;              break; }
        default: {}
      }
    }

    if (strlen($to) == 0)
      $errors[] = '- de ontvanger ontbreekt';

    $subject = null;
    if (array_key_exists('subject', $_POST))
      $subject = trim($_POST['subject']);

    if (strlen($subject) == 0)
      $errors[] = '- het onderwerp ontbreekt';

    $message = null;
    if (array_key_exists('message', $_POST))
      $message = trim($_POST['message']);

    if (strlen($message) == 0)
      $errors[] = '- het bericht is leeg';

    $captchaOk = false;
    if (array_key_exists('captcha-answer', $_POST) && array_key_exists('captcha-answer-given', $_POST))
      $captchaOk = $_POST['captcha-answer'] == preg_replace('/[\s\W]+/', '', strtolower($_POST['captcha-answer-given']));
    
    if (!$captchaOk)
      $errors[] = '- controlevraag is onjuist beantwoord';

    if (count($errors) >= 1)
    {
      $errorMessage = 'Er zijn enkele fouten gevonden in het formulier:' . "<br />" . implode("<br />", $errors);
      Util::addAlert($errorMessage, 'fail', false);
      return;
    }

    $message .=
      "\n" . "\n" .
      "Gegevens afzender: " . "\n" .
      "Naam: " . $name . "\n" .
      "Mobiel: " . $phone . "\n" .
      "E-mail: " . $email . "\n";

    $headers = sprintf(
      'From: Schaakclub Utrecht <info@schaakclubutrecht.nl>' . "\r\n" .
      'Reply-To: info@schaakclubutrecht.nl' . "\r\n" .
      'MIME-Version: 1.0' . "\r\n" .
      'Content-type: text/plain; charset=utf-8' . "\r\n"
    );

    $success = false;
    if (!is_null($to) && !is_null($subject) && !is_null($message))
      $success = mail($to, $subject, $message, $headers);
    
    if ($success)
      Util::addAlert('Uw e-mail is succesvol verstuurd. U ontvangt spoedig bericht.', 'success');
    else
      Util::addAlert('Er is iets mis gegaan met het versturen van uw e-mail. Probeer het opnieuw.');
    
    Util::redirectToIndex();
  }
}
