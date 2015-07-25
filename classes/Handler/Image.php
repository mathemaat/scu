<?php

class Handler_Image extends Handler_Resource
{
  public function search() {
    $filename = array_key_exists('filename',  $_GET) ? $_GET['filename'] : null;
    $page     = array_key_exists('page',      $_GET) ? $_GET['page']     : 1;

    $limit  = ITEMS_PER_PAGE;
    $offset = ITEMS_PER_PAGE * ($page - 1);

    $dbHandler = Application::getInstance()->getDBHandler();

    $query =
      "SELECT img_id, img_filename, imt_id, imt_name " .
      "FROM tblimage " .
      "INNER JOIN tblimagetype ON imt_id = img_imt_id " .
      "WHERE TRUE ";
    $params = array();

    if (strlen($filename) >= 1)
    {
      $param = '%' . $filename . '%';
      $query .= "AND img_filename LIKE ? ";
      $params = array_merge($params, array($param, $param));
    }

    $query .=
      "LIMIT " . $limit . " " .
      "OFFSET " . $offset;

    $statement = $dbHandler->prepare($query);
    $statement->execute($params);

    $items = $statement->fetchAll(PDO::FETCH_ASSOC);

    if (count($items) >= 1) {
      $result = '<table>';
      $result .= '<tr>' . sprintf(str_repeat('<th>%s</th>', 4), '#', 'Bestandsnaam', 'Type', 'Bekijken') . '</tr>';

      foreach($items as $item) {
        $url = sprintf('<a href="%s/image/view/%d">Bekijken</a>', APPLICATION_DOCROOT, $item['img_id']);

        $result .= '<tr>' . sprintf(str_repeat('<td>%s</td>', 4), $item['img_id'], $item['img_filename'], $item['imt_name'], $url) . '</tr>';
      }

      $result .= '</table>';
    }
    else
      $result = 'Geen resultaten';

    $variables = array(
      'docroot'  => APPLICATION_DOCROOT,
      'filename' => $filename,
      'result'   => $result
    );

    $template = Util::getTemplate('image-search');

    echo $this->formatTemplate(array('body' => Util::formatString($template, $variables)));
  }

  public function upload() {
    if (array_key_exists('save', $_POST) && array_key_exists('image', $_FILES)) {
      $image = $_FILES['image'];

      $filename  = $image['name'];
      $imageType = self::getImageType($image['type']);
      $contents  = file_get_contents($image['tmp_name']);

      if ($imageType && strlen($contents) >= 1) {
        $dbHandler = Application::getInstance()->getDBHandler();

        $query = "INSERT INTO tblimage (img_filename, img_contents, img_imt_id) VALUES (?, ?, ?)";
        $params = array($filename, $contents, $imageType['imt_id']);

        $statement = $dbHandler->prepare($query);
        $statement->execute($params);

        Util::redirect(sprintf('image/view/%d', $dbHandler->lastInsertId()));
      }
      else
        throw new Exception('Invalid image file');
    }
    else if (array_key_exists('cancel', $_POST))
      Util::redirect('image/search');

    $variables['docroot'] = APPLICATION_DOCROOT;

    $template = Util::getTemplate('image-upload');

    echo $this->formatTemplate(array('body' => Util::formatString($template, $variables)));
  }

  protected function getImageType($mimetype)
  {
    $dbHandler = Application::getInstance()->getDBHandler();

    $query =
      "SELECT imt_id, imt_name, imt_extension, imt_mimetype " .
      "FROM tblimagetype " .
      "WHERE imt_mimetype = ?";
    $params = array($mimetype);

    $statement = $dbHandler->prepare($query);
    $statement->execute($params);
    return $statement->fetch(PDO::FETCH_ASSOC);
  }

  public function view()
  {
    if (count($this->arguments) == 0)
      Router::notFound();

    $imageId = array_shift($this->arguments);

    $dbHandler = Application::getInstance()->getDBHandler();

    $query =
      "SELECT img_id, img_filename, img_contents, imt_id, imt_name, imt_extension, imt_mimetype " .
      "FROM tblimage " .
      "INNER JOIN tblimagetype ON imt_id = img_imt_id " .
      "WHERE img_id = ?";
    $params = array($imageId);

    $statement = $dbHandler->prepare($query);
    $statement->execute($params);
    $image = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$image)
      Router::notFound();

    $imageFile = sprintf('%s/static/img/%04d-%s', APPLICATION_PATH, $imageId, $image['img_filename']);
    if (!file_exists($imageFile))
    {
      $success = file_put_contents($imageFile, $image['img_contents']);
      if (!$success)
        throw new Exception('Unable to save image to disk');
    }

    $variables = array(
      'docroot'           => APPLICATION_DOCROOT,
      'uploaded-filename' => sprintf('%04d-%s', $imageId, $image['img_filename']),
      'filename'          => $image['img_filename'],
      'type'              => $image['imt_name']
    );

    $template = Util::getTemplate('image-view');

    echo $this->formatTemplate(array('body' => Util::formatString($template, $variables)));
  }
}
