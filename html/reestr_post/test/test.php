<?php
chdir('/var/www/html/reestr_post');
require_once "config.php";

  function writeResponceToFile($file)
  {
      $f = fopen($file, 'w');
      $data = file_get_contents($file);
      //fwrite($f, $this->responce);
      fclose($f);

      //$data = preg_replace( "/\r|\n/", "", $data );

      //$data = str_replace('<th class="left vtop">Получатель выписки:</th>', "nimbsymb", $data);


      //Загружаем документ
      $doc = new DOMDocument();
      $doc->loadHTML($data);

      $trs = $doc->getElementsByTagName('tr');
      $nodeId = false;


      foreach ($trs as $key => $tr) {
          //Ищем получаетля и берем последний ключ (<tr> его содержащий)
          $pos = strpos($tr->nodeValue, "олучатель");
          if($pos !== false){
            $nodeId = $key;
          }
      }

      //Если таковой найден / удаляем его и всех его деток
      if($nodeId){
        $element = $trs[$nodeId];
        $element->parentNode->removeChild($element);
        $data = $doc->saveHTML();
      }

      print_r($data);
  }


  $file = "/var/www/html/reestr_post/test/7.html";
  writeResponceToFile($file);

  //http://5.45.124.250/reestr_post/test/test.php
?>
