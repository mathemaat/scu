<?php

class Handler_Upload extends Handler
{
  public function handleRequest() {
    if (count($this->arguments) == 0)
      Router::notFound();

    // use hardcoded list for now
    $whitelist = array('213.10.71.43');
    if ($_SERVER['HTTP_HOST'] != 'localhost' && !in_array(Util::getIPAddress(), $whitelist))
      Router::notFound();

    $uploadType = strtolower(array_shift($this->arguments));
    switch ($uploadType) {
      case 'pgn': $this->handleRequestPGN();  break;
      default: Router::notFound();
    }
  }

  public function handleRequestPGN() {
    if (array_key_exists('save', $_POST) && array_key_exists('contents', $_POST)) {
      $contents = trim($_POST['contents']);

      if (strlen($contents) >= 1) {
        $dbHandler = Application::getInstance()->getDBHandler();

        $query = "INSERT INTO tblpgn (pgn_contents) VALUES (?)";
        $params = array($contents);

        $statement = $dbHandler->prepare($query);
        $statement->execute($params);
      }
    }

    $variables['docroot']  = APPLICATION_DOCROOT;

    $template = Util::getTemplate('upload-pgn');

    echo $this->formatTemplate(array('body' => Util::formatString($template, $variables)));
  }
}