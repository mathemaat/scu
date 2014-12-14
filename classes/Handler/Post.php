<?php

class Handler_Post extends Handler_Page
{
  // de artikelen van het geselecteerde menuitem en tabblad
  protected static $post;

  public function __construct($arguments) {
    parent::__construct($arguments);

    self::$post = $this->getPost();
  }

  public function getMenuItem() {
    $dbHandler = Application::getInstance()->getDBHandler();

    $query =
      'SELECT mni_id, mni_description, mni_token ' .
      'FROM tblpost ' .
      'INNER JOIN tblmenuitem ON mni_id = pst_mni_id ' .
      'WHERE pst_id = ?';
    $params = array($this->arguments[0]);
    $statement = $dbHandler->prepare($query);
    $statement->execute($params);

    return $statement->fetch(PDO::FETCH_ASSOC);
  }

  // bepaal a.d.h.v. het ID van het artikel bij welk tabblad en seizoen het hoort
  public function setGETParams() {
    $dbHandler = Application::getInstance()->getDBHandler();

    $query =
      "SELECT pst_sea_id AS 'season', IFNULL(tab_token, '') AS 'tab' " .
      'FROM tblpost ' .
      'LEFT JOIN tbltab ON tab_id = pst_tab_id ' .
      'WHERE pst_id = ?';
    $params = array($this->arguments[0]);
    $statement = $dbHandler->prepare($query);
    $statement->execute($params);

    return $statement->fetch(PDO::FETCH_ASSOC);
  }

  protected function getPost() {
    $dbHandler = Application::getInstance()->getDBHandler();

    $query =
      'SELECT pst_id, pst_date, pst_title, pst_contents ' .
      'FROM tblpost ' .
      'WHERE pst_id = ?';
    $params = array($this->arguments[0]);
    $statement = $dbHandler->prepare($query);
    $statement->execute($params);

    return $statement->fetch(PDO::FETCH_ASSOC);
  }

  public function handleRequest() {
    $variables['class']    = 'post';
    $variables['tabs']     = $this->getTabsHtml();
    $variables['title']    = self::$selectedTab['tab_description'];
    $variables['contents'] = $this->getPostHtml();

    $template = Util::getTemplate('page');

    $body = $this->getSeasonWindow() . Util::formatString($template, $variables);

    echo $this->formatTemplate(array('body' => $body));
  }

  protected function getPostHtml() {
    $template = Util::getTemplate('post');

    $variables = array(
      'docroot'   => APPLICATION_DOCROOT,
      'post-id'   => self::$post['pst_id'],
      'title'     => self::$post['pst_title'],
      'meta-data' => date('d-m-Y', strtotime(self::$post['pst_date'])),
      'contents'  => self::$post['pst_contents']
    );

    return Util::formatString($template, $variables);
  }
}