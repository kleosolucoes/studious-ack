<?php
namespace Facebook\WebDriver;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverKeys;
use Facebook\WebDriver\Conexao;

require_once('vendor/autoload.php');

$link = mysqli_connect('br130.hostgator.com.br', 'zapma087_novo', 'zP7KQbV[7G97', 'zapma087_zap');

if (!$link) {
  echo "Error: Unable to connect to MySQL." . PHP_EOL;
  echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
  echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
  exit;
}

echo "Success: A proper connection to MySQL was made! The my_db database is great." . PHP_EOL;
echo "Host information: " . mysqli_get_host_info($link) . PHP_EOL;

//$idBot = $_GET['id'];
//if(!$idBot){
  $idBot = 1;
//}

$buscaDataAlteracao = function($link, $idBot = 1){
$sqlBot = "SELECT data_alteracao, hora_alteracao FROM bot WHERE id = $idBot;";

$resultBot = mysqli_query($link, $sqlBot);

while($row = mysqli_fetch_array($resultBot)){
    $resposta = $row['data_alteracao'] . $row['hora_alteracao'];
}

return $resposta;
};

$pegaMensagens = function($link, $idBot = 1){

unset($mensagens);
$sqlBot = "SELECT mensagem, data_alteracao, hora_alteracao FROM bot WHERE id = $idBot;";

mysqli_set_charset($link, 'latin1');
$resultBot = mysqli_query($link, $sqlBot);


while($row = mysqli_fetch_array($resultBot)){
  $mensagens[0][0] = utf8_encode($row['mensagem']);
  $dataAlteracaoNova = $row['data_alteracao'] . $row['data_alteracao'];
  $dataAlteracaoVelha = $dataAlteracaoNova;
}

$sqlBotOpcao = "SELECT titulo, resposta FROM bot_opcao WHERE bot_id = 1;";
$resultBotOpcao = mysqli_query($link, $sqlBotOpcao);
$contadorDeOpcoes = 0;
while($row = mysqli_fetch_array($resultBotOpcao)){
  $contadorDeOpcoes++;
  $mensagens[0][$contadorDeOpcoes] = "$contadorDeOpcoes - " . utf8_encode($row['titulo']);
  $mensagens[$contadorDeOpcoes] = "$contadorDeOpcoes - " . utf8_encode($row['titulo']) . ": " . utf8_encode($row['resposta']);
}

 echo "Peguei as mensagens" . PHP_EOL;

 return $mensagens;
};

$dataVelha = '';
$dataNova = $buscaDataAlteracao($link);
$mensagens = $pegaMensagens($link);

echo "\n Total de mensagens: " . count($mensagens);

$host = 'http://localhost:4444/wd/hub';
$capabilities = DesiredCapabilities::firefox();
$capabilities->setCapability("webdriver.load.strategy", "unstable");
$driver = RemoteWebDriver::create($host, $capabilities, 60000, 120000);

$driver->get('http://web.whatsapp.com/');

echo "\n Esperando 5 segundos";
sleep(5);
echo "\n Esperando 5 segundos";
sleep(5);

$driver->manage()->timeouts()->implicitlyWait(20);

echo "\n Loop infinito de procura de novas mensagens";

while (true) {

	echo "\n\n Verificando as datas de alteracao";
	echo "\n dataNova $dataNova != dataVelha $dataVelha";
	if($dataNova != $dataVelha){
		echo "\n datas diferentes";
		$mensagens = $pegaMensagens($link);
		$dataVelha = $dataNova;
	}

  echo "\n Verificando se tem alguma mensagem nova";
  $mensagensNaoLidas = $driver->findElements(WebDriverBy::cssSelector("span.unread-count.icon-meta"));

  if (count($mensagensNaoLidas)) {

    echo "\n\n ##### Tenho mensagens nao lidas #####";
    //sleep(1);
    echo "\n Procurando Contatos";
    $divListaInfinita = $driver->findElements(WebDriverBy::cssSelector("div.infinite-list-item.infinite-list-item-transition"));

    unset($listaDeContatos);
    if (count($divListaInfinita) > 0) {
      echo "\n\n Achei a lista de contatos";
      //sleep(1);
      echo "\n Foreach da lista de contatos";

	$contagemDePessoasComMensagens = 0;
      foreach ($divListaInfinita as $divParaVerificar) {

        echo "\n Rodando foreach";
        $data = explode("\n", $divParaVerificar->getText());
        if (count($data) === 4) {
	  $nome = $data[0];
          echo "\n\n\n Contato com mensagem: " . $nome;
	  if(substr($nome,0,1) == '+'){
		$nomeAjustado = str_replace('+', '', $nome);
		$nomeAjustado = str_replace('-', '', $nomeAjustado);
		$nomeAjustado = str_replace(' ', '', $nomeAjustado);
		$nome = $nomeAjustado;
	  }
          $listaDeContatos[] = $nome;
          echo "\n Quantidade: " . $data[3];
          //sleep(1);
		$contagemDePessoasComMensagens++;
        }// if se tem mensagem nova

	if($contagemDePessoasComMensagens === count($mensagensNaoLidas)){
		break;
	}
      }// for dos contatos
    } else {// lista de contatos
      echo "\n\n Sem mensagens novas";
    }

    echo "\n\n\n Atendendo mensagens da ultima a primeira";
    echo "\n\n\n Contatos Com Mensagem: " . count($listaDeContatos);
    for($indiceContatos = count($listaDeContatos) - 1; $indiceContatos >= 0; $indiceContatos--){
      echo "\n Procurando busca de contato: " . $listaDeContatos[$indiceContatos];
      $inputFiltro = $driver->findElement(WebDriverBy::cssSelector("input.input-search"));
      $inputFiltro->sendKeys($listaDeContatos[$indiceContatos]);
      sleep(1);

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

        echo "\n Procurando mensagens recebidas";
        $mensagensRecebidas = $driver->findElements(WebDriverBy::cssSelector("div.message.message-chat.message-in.message-chat"));
        sleep(1);

        $quantidadeDeMensagens = count($mensagensRecebidas);
        if ($quantidadeDeMensagens > 0) {
          echo "\n Achei as mensagens";
          echo "\n Ultima mensagem: ";
          $arrayMensagem = explode("\n", $mensagensRecebidas[($quantidadeDeMensagens - 1)]->getText());
          $ultimaMensagem = $arrayMensagem[(count($arrayMensagem) - 2)];
          echo "\n ##### " . $ultimaMensagem . " #####";
          echo "\n esperando 1 segundos";
          sleep(1);
          echo "\n Escrevendo";
          $elementoInputMensagem = $driver->findElement(WebDriverBy::xpath("//div[@contenteditable='true']"));
          $resposta = '';
          $adicionarOpcoes = false;
          switch ($ultimaMensagem) {
            case 1:
            $resposta = $mensagens[$ultimaMensagem];
            break;
            case 2:
            $resposta = $mensagens[$ultimaMensagem];
            break;
            case 3:
            $resposta = $mensagens[$ultimaMensagem];
            break;
            case 4:
            $resposta = $mensagens[$ultimaMensagem];
            break;
            case 5:
            $resposta = $mensagens[$ultimaMensagem];
            break;
            default:
            $resposta = $mensagens[0][0];
            $adicionarOpcoes = true;
            break;
          }

	  $explodeResposta = explode(PHP_EOL, $resposta);
	  if(count($explodeResposta) > 1){
		foreach($explodeResposta as $linha){
			$elementoInputMensagem
				->sendKeys(array(
					$linha,
					WebDriverKeys::SHIFT,
					WebDriverKeys::ENTER,
				));
			sleep(0.1);
		}
	  }else{
	  	$elementoInputMensagem->sendKeys($resposta);
	  }
          sleep(1);
          echo "\n Escrito";
          if($adicionarOpcoes){

            for($indiceMensagem = 1; $indiceMensagem <= (count($mensagens)-1); $indiceMensagem++){
              $elementoInputMensagem
              ->sendKeys(array(
                WebDriverKeys::SHIFT,
                WebDriverKeys::ENTER,
              ));
              sleep(0.1);
              $elementoInputMensagem
              ->sendKeys(array(
                $mensagens[0][$indiceMensagem],
              ));
              sleep(0.1);
            }
          }

          sleep(0.1);
          $elementoInputMensagem
          ->sendKeys(array(
            WebDriverKeys::SHIFT,
            WebDriverKeys::ENTER,
          ));
          sleep(0.1);
          $elementoInputMensagem
          ->sendKeys(array(
            WebDriverKeys::SHIFT,
            WebDriverKeys::ENTER,
          ));

          sleep(0.1);
          $elementoInputMensagem
          ->sendKeys(array(
            'Digite "0" para voltar ao início!',
          ));
          echo "\n Botao de enviar";
          $driver->findElement(WebDriverBy::cssSelector("button.compose-btn-send"))->click();
          sleep(2);
          echo "\n Enviado";
        }
      }else{
        echo "\n ????? Nao encontrei o contato ?????";
      }
    }//fim for do array

    $driver->navigate()->refresh();
    echo "\n\n\n\n Esperando 2 segundos antes de tentar achar as mensagens";
    sleep(2);
  }

$dataNova = $buscaDataAlteracao($link);
}
