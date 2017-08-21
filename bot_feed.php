<?php
namespace Facebook\WebDriver;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverKeys;
use SimpleXmlElement;

require_once('vendor/autoload.php');



$host = 'http://localhost:4444/wd/hub';
$capabilities = DesiredCapabilities::firefox();
$capabilities->setCapability("webdriver.load.strategy", "unstable");
$driver = RemoteWebDriver::create($host, $capabilities, 60000, 120000);

$driver->get('http://web.whatsapp.com/');

//echo "\n Esperando 5 segundos";
sleep(5);
//echo "\n Esperando 5 segundos";
sleep(5);

$driver->manage()->timeouts()->implicitlyWait(20);


$feed = file_get_contents('http://g1.globo.com/dynamo/rss2.xml');
//$feed = file_get_contents('http://www.empregos.net/rss/informatica/rss.xml');
$rss = new SimpleXmlElement($feed);

foreach($rss->channel->item as $item){
	echo $item->title . PHP_EOL . PHP_EOL;
      
      echo "\n Procurando contato: Pr Kort";
      $inputFiltro = $driver->findElement(WebDriverBy::cssSelector("input.input-search"));
      $inputFiltro->sendKeys('Pr Kort');
      sleep(2);

      echo "\n Clicando no contato ou mensagem";
      $encontrado = false;
      try{
        $driver->findElement(WebDriverBy::cssSelector("div.chat-body"))->click();
        $encontrado = true;
      }catch(NoSuchElementException $exc){
        echo "\n\n Nao encontrou o contato";
      }

      if($encontrado){
	echo "\n Encontrado";
        sleep(1);
	$elementoInputMensagem = $driver->findElement(WebDriverBy::xpath("//div[@contenteditable='true']"));
	$elementoInputMensagem->sendKeys("{$item->title}");
	$elementoInputMensagem
          ->sendKeys(array(
            WebDriverKeys::SHIFT,
            WebDriverKeys::ENTER,
          ));
        sleep(0.1);
	$elementoInputMensagem->sendKeys("{$item->link}");
	echo "\n Botao de enviar";
          $driver->findElement(WebDriverBy::cssSelector("button.compose-btn-send"))->click();
          sleep(5);
          echo "\n Enviado";
      }
	$driver->navigate()->refresh();
	sleep(10);
}
