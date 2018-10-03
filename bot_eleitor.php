<?php
namespace Facebook\WebDriver;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\LocalFileDetector;
use Facebook\WebDriver\WebDriverKeys;
use Exception;

require_once('vendor/autoload.php');

$host = 'http://localhost:4444/wd/hub';
$capabilities = DesiredCapabilities::chrome();
$capabilities->setCapability("webdriver.load.strategy", "unstable");
$driver = RemoteWebDriver::create($host, $capabilities, 60000, 120000);

$bloco = 0;
if($bloco = $argv[1]);
$driver->get('https://circuitodavisaonovo.com.br/deployEleitor/'.$bloco);

sleep(2);
$driver->getKeyboard()->sendKeys(
	array(WebDriverKeys::CONTROL, 't')
);

$handles = $driver->getWindowHandles();
$driver->switchTo()->window($handles[1]);

$driver->get('https://web.whatsapp.com/');
sleep(6);
$driver->close();

$driver->switchTo()->window($handles[0]);
if($elementos = $driver->findElements(WebDriverBy::cssSelector("a.botaoEnvio"))){
	echo "\n Encontrou tantos botoes: ".count($elementos);
	$contador = 1;
	foreach($elementos as $elemento){
		$coordenadaX = $elemento->getCoordinates()->onPage()->getX();
		$coordenadaY = $elemento->getCoordinates()->onPage()->getY();
		try{
			$driver->executeScript("window.scrollTo(".$coordenadaX.", ".$coordenadaY.");");
		}catch(Exception $e){
			echo "\n\n Error executeScript: ".$e->getMessage();
		}

		$idEleitor = $elemento->getText();
		echo "\n idEleitor: ".$idEleitor;
		sleep(rand(3,4));

		echo "\n clicando no botao";
		$elemento->click();

		$handles = $driver->getWindowHandles();
		$driver->switchTo()->window($handles[1]);
		echo "\n esperando 5";
		sleep(rand(5,7));
		echo "\n Botao whatsapp 1";
		$botaoEnviar1 = $driver->findElement(WebDriverBy::xpath("//*[@id='action-button']"));
		$botaoEnviar1->click();

		echo "\n esperando 10";
		sleep(rand(9,12));

		$mensagemEnviada = true;
		try{	
			$driver->findElement(WebDriverBy::xpath('//*[@id="app"]/div/span[3]/div/span/div/div/div/div/div/div[2]/div'));
			$mensagemEnviada = false;
		} catch(Exception $e){
			$anexar = $driver->findElement(WebDriverBy::xpath('//*[@id="main"]/header/div[3]/div/div[2]/div'));
			$anexar->click();
			$inputFile = $driver->findElement(WebDriverBy::xpath('//*[@type="file"]'));
			$inputFile->setFileDetector(new LocalFileDetector());
			$remote_image = rand(1,3) . '.jpg';
			$inputFile->sendKeys($remote_image);
			sleep(rand(1,4));
			//echo "\n apertando enter";
			//$botaoEnviar2 = $driver->findElement(WebDriverBy::xpath('//*[@id="app"]/div/div/div[1]/div[2]/span/div/span/div/div/div[2]/span[2]/div/div'));
			//$botaoEnviar2->click();
			//sleep(rand(1,3));
			//$driver->findElement(WebDriverBy::xpath('//*[@id="main"]/footer/div[1]/div[3]/button'))->click();
			//sleep(rand(1,3));
		}
		$driver->close();

		$handles = $driver->getWindowHandles();
		$driver->switchTo()->window($handles[1]);
		$driver->close();

		sleep(rand(0,2));
		$driver->switchTo()->window($handles[0]);

		$labelBotao = '';
		if($mensagemEnviada){
			$labelBotao = 'botaoEnviado';
		}else{
			$labelBotao = 'botaoInvalido';
		}
		try{
			$driver->findElement(WebDriverBy::xpath("//*[@id='{$labelBotao}{$idEleitor}']"))->click();
		}catch(Exception $e){
			echo "\n\n Exception: ".$e->getMessage();
		}

		if($contador % 10 === 0){
			echo "\n page down";
		}
		if($contador === 20){
			//break;
		}
		$contador++;
	}
}
$driver->quit();

