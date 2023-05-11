<?php

namespace App\Http\Controllers\Api;

use App\Models\Cep;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Validation\ValidationException;


class CepController extends Controller
{
    public function index($cep)
    {
        //chamada de validator, onde tem as rules e as messagens logo abaixo
        $validator = Validator::make(['cep' => $cep], $this->rules(), $this->messages());
        
        //try catch se a validação for bem sucedida, se não é retornado o erro e a mensagem
        try {
            $validator->validate();
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Erro de Validação.',
                'errors' => $e->errors(),
            ], 422);
        }

        //Instanciação da classe cliente da biblioteca GuzzleHttp
        $client = new Client([
            'base_uri' => 'viacep.com.br/ws/',
            'timeout'  => 2.0,
        ]);
        
        //Salva historico de ceps acessados
        Cep::create(['cep' => $cep]);
        
        try{
        //envia a request para a api viacep
        $response = $client->get($cep . '/json');
        
        $body = $response->getBody();
        return json_decode($body, true);
        
        } catch (RequestException $e) {
            // catch com erros de conexão, como timeout, DNS falhando, etc.
            return response()->json(['message' => 'Erro ao conectar à API.'], 500);
        } catch (\Exception $e) {
            // catch erros genéricos, como uma resposta malformada
            return response()->json(['message' => 'Erro ao processar resposta da API.'], 500);
        }
    }
    
    private function rules()
    {
        return [
            'cep' => ['required','integer','digits:8'],
        ];
    }

    private function messages(){
    return [
        'cep.required' => 'O campo CEP é obrigatório.',
        'cep.integer' => 'O campo CEP deve ser um número inteiro.',
        'cep.digits' => 'O campo CEP deve ter exatamente 8 dígitos.',
    ];
}    
}
