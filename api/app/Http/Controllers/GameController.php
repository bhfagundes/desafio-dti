<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
class GameController extends Controller
{

    public function jogadorInicial()
    {
        /* Para sortear o jogador inicial definimos:
        0 - para x
        1 - para O */
        if( rand(0,1) == 0)
        {
            $jogador = "X";
        }
        else
        {
            $jogador = "O";
        } 
        return $jogador;
    }
    public function iniciarTabuleiro($jogador)
    {
        $sessao=Hash::make( Carbon::now()->timestamp);
        $sessao = str_replace("\/",'-',$sessao);
        $sessao = str_replace("/",'-',$sessao);
        Storage::disk('local')->put($sessao.'.txt', $jogador . "\n");
        return $sessao;
    }
    public function start()
    {
        $jogador = $this->jogadorInicial();
        $sessao = $this->iniciarTabuleiro($jogador);
        $retorno['firstPlayer']=$jogador;
        $retorno['id']=$sessao;
        return json_encode($retorno);
    }
    public function lerArquivo($id)
    {
        $contents = Storage::get($id.'.txt'); 
        $contents = explode("\r\n",$contents);
        return $contents;
    }
    public function recuperarJogador($id)
    {

        $contents =$this->lerArquivo($id);
        $jogador = $contents[0];
        //Caso fique algum lixo na string
        if(strstr($jogador, 'O'))
        {
            $jogador = 'O';
        }
        else
        {
            $jogador = 'X';
        }
        return $jogador;
    }
    public function montarMatrizJogadas($contents)
    {
        //nao é preciso verificar a ultima linha, so indica quem começou o jogo
        for($i=0;$i <3 ; $i++)
        {
            for($j=0; $j<3 ; $j++)
            {
                $matriz[$i][$j] = '0';
            }
        
        }
        for($i=0; $i<sizeof($contents)-1 ; $i++)
        {
            $conteudo = explode(" ",$contents[$i+1]);
            $x=$conteudo[0];
            $y=$conteudo[1];
            $matriz[$x][$y] = $contents[$i] ;
            $i++;
        }
        return $matriz;
    }
    public function verificaLinhas($matriz)
    {
        $contadorX=0;
        $contadorO = 0;
        for ($i =0; $i <3; $i++)
        {
            if($matriz[$i][0] == "X" && $matriz[$i][1] == "X" && $matriz[$i][2] == "X")
            {
                $contadorX =1;
                $i=3;
            }
            else if($matriz[$i][0] == "O" && $matriz[$i][1] == "O" && $matriz[$i][2] == "O")
            {
                $contadorO = 1;
                $i=3;   
            }
        }
        if( $contadorX >0 )
        {
            return 'X';
        }
        else if($contadorO >0 )
        {
            return 'O';
        }
        else
        {
            return '.';
        }
    }
    public function verificaColunas($matriz)
    {
        $contadorX=0;
        $contadorO = 0;
        for ($i =0; $i <3; $i++)
        {
            if($matriz[0][$i] == "X" && $matriz[1][$i] == "X" && $matriz[2][$i] == "X")
            {
                $contadorX =1;
                $i=3;
            }
            else if($matriz[0][$i] == "O" && $matriz[1][$i] == "O" && $matriz[2][$i] == "O")
            {
                $contadorO = 1;
                $i=3;   
            }
        }
        if( $contadorX >0 )
        {
            return 'X';
        }
        else if($contadorO >0 )
        {
            return 'O';
        }
        else
        {
            return '.';
        }
    }
    public function verificaDiagonais($matriz)
    {
        $contadorX=0;
        $contadorO = 0;
        for ($i =0; $i <3; $i++)
        {
            if(($matriz[0][0] == "X" && $matriz[0][0] == "X" && $matriz[2][2] == "X")
                || ($matriz[0][2]== "X" && $matriz[1][1] == "X" && $matriz[2][0]=="X")
            )
            {
                $contadorX =1;
            }
            else if(($matriz[0][0] == "O" && $matriz[0][0] == "O" && $matriz[2][2] == "O")
                ||   ($matriz[0][2]== "O" && $matriz[1][1] == "O" && $matriz[2][0]=="O")
            )
            {
                $contadorO = 1; 
            }
        }
        if( $contadorX >0 )
        {
            return 'X';
        }
        else if($contadorO >0 )
        {
            return 'O';
        }
        else
        {
            return '.';
        }
    }
    public function verificaGanhador($matriz, $nLinhas)
    {
       
        $linhas = $this->verificaLinhas($matriz);
        $colunas = $this->verificaColunas($matriz);
        $diagonais =$this->VerificaDiagonais($matriz);   
        if($linhas == 'X' || $colunas == 'X' || $diagonais== 'X')
        {
            return response()->json(['status' => 'Partida finalizada', 'winner' => 'X']); 
        }
        else if($linhas == 'O' || $colunas == 'O' || $diagonais== 'O')
        {
            return response()->json(['status' => 'Partida finalizada', 'winner' => 'O'],200);
        }
        else if($nLinhas>17)
        {
            return response()->json(['status' => 'Partida finalizada', 'winner' => 'Draw']);  
        }
        else
        {
            return response()->json( 200);
        }

    }
    public function checarResultado($id)
    {
        /*precisamos de pelo menos 5 jogadas para que tenhamos uma definição do jogo
        //como temos 1 linha pra definir quem inicia
        // 2 linhas pra cada jogada do usuario : 1 indicar usuario, 1 indicar coordenadas
        // precisamos que se tenha 11 linhas no arquivo para vitoria ou velha
        */
        $contents = $this->lerArquivo($id);
        $nLinhas= sizeof($contents);
        if(sizeof($contents) < 11)
        {
            return response()->json( 200);
        }
        else
        {
            $matriz = $this->montarMatrizJogadas($contents);
            return  $verificarGanhador= $this->verificaGanhador($matriz,$nLinhas);
        }        

    }
    public function movement($id)
    {
        $parametros = Request()->all();
        /* Definindo como regra, sempre a primeira linha será o ultimo jogador que movimentou*/
        if (Storage::exists($id.'.txt'))
        {
            $jogador = $this->recuperarJogador($id);
            $contents = $this->lerArquivo($id);
            $nLinhas= sizeof($contents);
            if($jogador == $parametros['player'] && $nLinhas>1)
            {
                $msg="Não é turno do jogador";
                return  response()->json($msg); 
            }
            else if($jogador == $parametros['player'] && $nLinhas ==1)
            {
                Storage::prepend($id.'.txt',$parametros['position']['x'] . " " . $parametros['position']['y']);
                Storage::prepend($id.'.txt',$parametros['player']);
                return response()->json( 200);

            }
            else if($jogador != $parametros['player'] && $nLinhas ==1)
            {
                $msg="Não é turno do jogador";
                return  response()->json($msg); 
            }
            Storage::prepend($id.'.txt',$parametros['position']['x'] . " " . $parametros['position']['y']);
            Storage::prepend($id.'.txt',$parametros['player']);
            return $this->checarResultado($id); 
        }
        else
        {
            $msg="Partida não encontrada";
            return  response()->json($msg);              
        }
         
    }
}
