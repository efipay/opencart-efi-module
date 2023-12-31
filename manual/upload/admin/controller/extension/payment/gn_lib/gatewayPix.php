<?php

if (version_compare(phpversion(), '5.4.0', '>=')) {
	include_once('gerencianet/vendor/autoload.php');
} else {
	echo "A versão do PHP instalado no servidor não é compatível com o módulo da Gerencianet. Por favor, verifique os requisitos do módulo.";
	die();
}

use Gerencianet\Exception\GerencianetException;
use Gerencianet\Gerencianet;

/**
 * Gateway de Pagamento, responsavel em realizar a comunicação entre a API-Gerencianet e o OpenCart
 */
class GatewayPix {
    
    /**
     * Instância da API-Gerencianet
     * @access private
     * @var array
     */
    private $apiInstance = [];

    /**
     * Precisa instânciar a classe Gateway passando as configurações salvas
     * @param array $optionConfig
     */
    public function __construct($optionConfig) {
        $this->apiInstance = $this->getGerencianetInstance($optionConfig);
    }

    /**
     * Cria uma instância da API-Gerencianet com as credenciais do cliente
     * @param array $dataConfigPix
     * @return array
     */
    private function getGerencianetInstance($dataConfigPix) {
        try {
            $newInstance = new Gerencianet(
                Array(
                    // OpenCart retorna 1 para true e NULL para false
                    'client_id' => $dataConfigPix['sandbox'] == 1 ? $dataConfigPix['clientIdDev'] : $dataConfigPix['clientIdProd'],
                    'client_secret' => $dataConfigPix['sandbox'] == 1 ? $dataConfigPix['clientSecretDev'] : $dataConfigPix['clientSecretProd'],
                    'certificate' => $dataConfigPix['certificate'],
                    'sandbox'=> $dataConfigPix['sandbox']==1,
                    'debug' =>  $dataConfigPix['debug'] == 1,
                    'headers' => [
                        'x-skip-mtls-checking' => $dataConfigPix['mtls'] == 1 ? 'false' : 'true' //precisa ser string
                    ]
                )
            );
            return $newInstance;

        } catch (GerencianetException $e) {
            // throw new Error($e->error);
            $this->log->write("ERRO : " . $e->error);
            ToasterMessage::getInstance()->add(ToasterMessage::DANGER,  $e->error);
        } catch (Exception $e) {
            // throw new Error($e->getMessage());
            $this->log->write("ERRO : " . $e->getMessage());
            ToasterMessage::getInstance()->add(ToasterMessage::DANGER,  $e->getMessage());
        }
    }

    /**
     * Registra um novo WebHook
     * @param string $pixKey
     * @param string $url
     * 
     * @return array
     */
    public function registerWebhook($pixKey, $url) {
       
        $params = ['chave' => $pixKey];
        $body = ['webhookUrl' => $url];
        
        return $this->apiInstance->pixConfigWebhook($params, $body);
    }

    public function registerWebhookOF($ofConfig, $url, $redirectUrl) {
        try {
            if ($ofConfig['mtls'] == 1) {
                $webhookSecurity =["type"=>"mtls" ];
            }else{
                $hash = hash('sha256',$ofConfig['clientIdProd']);

                $webhookSecurity =["type"=>"hmac","hash"=>$hash ];
            }
            $body = [

                'webhookURL' => $url,
    
                'redirectURL' => $redirectUrl,
    
                'webhookSecurity' => $webhookSecurity
    
            ];


            return $this->apiInstance->ofConfigUpdate($params = [] , $body);

        } catch(GerencianetException $e) {
            throw new Error($e->error);
        } catch(Exception $e) {
            throw new Error($e->getMessage());
        }
    }
}