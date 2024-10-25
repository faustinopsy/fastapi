<?php
namespace Fast\Api\Rotas;

use ReflectionClass;
use ReflectionMethod;
use phpDocumentor\Reflection\DocBlockFactory;

class DocBlockRouter {
    private array $rotas = [];

    public function passaControlador(string $classeControladora) {
        $reflexaoControladora = new ReflectionClass($classeControladora);
        $metodos = $reflexaoControladora->getMethods(ReflectionMethod::IS_PUBLIC);
        $fabricaDocBlock = DocBlockFactory::createInstance();
        foreach ($metodos as $metodo) {
            $comentarioDoc = $metodo->getDocComment();
            if ($comentarioDoc) {
                $docBlock = $fabricaDocBlock->create($comentarioDoc);
                $metodosHttp = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'];
                foreach ($metodosHttp as $metodoHttp) {
                    if ($docBlock->hasTag($metodoHttp)) {
                        $tagRota = $docBlock->getTagsByName($metodoHttp)[0];
                        $conteudo = $tagRota->getDescription()->render();
                        preg_match('/\("(.*)"\)/', $conteudo, $correspondencias);
                        $caminho = $correspondencias[1] ?? '';
                        $this->rotas[$metodoHttp][$caminho] = [$classeControladora, $metodo->getName()];
                    }
                }
            }
        }
    }

    public function resolve($metodoHttp, $uri) {
        if (!isset($this->rotas[$metodoHttp])) {
            http_response_code(405);
            echo json_encode(['status' => false, 'message' => 'Método não permitido']);
            exit();
        }
        $uri = parse_url($uri, PHP_URL_PATH);
        foreach ($this->rotas[$metodoHttp] as $rota => $acao) {
            $padrao = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', function ($matches) {
                $nomeParametro = $matches[1];
                if ($nomeParametro === 'nome') {
                    return '(?P<' . $nomeParametro . '>[a-zA-Z0-9@.\-_%+]+)';
                }
                return '(?P<' . $nomeParametro . '>[^/]+)';
            }, $rota);
            $padrao = '#^' . $padrao . '$#u';
            if (preg_match($padrao, $uri, $correspondencias)) {
                $instanciaControladora = new $acao[0]();
                $nomeMetodo = $acao[1];
                $parametros = array_filter(
                    $correspondencias,
                    fn($chave) => is_string($chave),
                    ARRAY_FILTER_USE_KEY
                );
                $dados = json_decode(file_get_contents('php://input'), true);
                $metodoRefletido = new ReflectionMethod($instanciaControladora, $nomeMetodo);
                $parametrosMetodo = $metodoRefletido->getParameters();
                $argumentos = [];
                foreach ($parametrosMetodo as $parametro) {
                    $nome = $parametro->getName();
                    if (isset($parametros[$nome])) {
                        $argumentos[] = $parametros[$nome];
                    } elseif ($nome === 'dados') {
                        $argumentos[] = $dados;
                    } else {
                        $argumentos[] = null;
                    }
                }
                return call_user_func_array([$instanciaControladora, $nomeMetodo], $argumentos);
            }
        }
        http_response_code(404);
        echo json_encode(['status' => false, 'message' => 'Rota não encontrada']);
        exit();
    }
}
