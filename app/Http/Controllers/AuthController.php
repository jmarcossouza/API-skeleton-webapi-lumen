<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequestException;
use App\Jobs\ConfirmarEmailJob;
use App\Jobs\RedefinirSenhaJob;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    private $auth;
    public function __construct()
    {
        $this->auth = app('auth');
        $this->middleware('auth:api', ['only' => []]);
    }

    public function store(Request $request, Usuario $usuario)
    {
        $this->validate($request, $usuario->regras_validacao);

        $novo_usuario = $usuario->create($request->all());
        return response()->json($novo_usuario, 201);
    }

    public function login(Request $request, Usuario $usuario)
    {
        $this->validate($request, ['email' => 'required', 'senha' => 'required']);
        $usuario = $usuario->where('email', $request->input('email'))->first();
        if ($usuario == null) {
            throw new InvalidRequestException('Não há um usuário com uma conta associada ao e-mail informado.');
        }

        $credenciais = [
            "email" => $request->input('email'),
            "password" => $request->input('senha')
        ];

        if (!$token = $this->auth->attempt($credenciais)) {
            throw new InvalidRequestException("Senha incorreta.");
        }

        if ($usuario->token_confirmar_email != null) {
            throw new InvalidRequestException("Sua conta ainda não foi confirmada, verifique seu e-mail. Caso necessário, enviaremos outro e-mail de confirmação.");
        }
        return $this->respondWithToken($token);
    }

    public function reenviarConfirmarEmail(Request $request)
    {
        $this->validate($request, ['email' => 'required']);
        $usuario = Usuario::where('email', $request->input('email'))->first();

        if ($usuario == null) {
            throw new ModelNotFoundException();
        } else if ($usuario->token_confirmar_email == null) {
            throw new InvalidRequestException('Sua conta já está ativada.');
        }

        DB::beginTransaction();
        try {
            $usuario->token_confirmar_email = Usuario::tokenUnico();
            $usuario->save();

            dispatch(new ConfirmarEmailJob($usuario));
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }

        return response()->json(['mensagem' => 'Enviamos novamente um e-mail para confirmar sua conta.'], 200);
    }

    public function confirmarEmail(Request $request)
    {
        $this->validate($request, ['token' => 'required']);
        $usuario = Usuario::where('token_confirmar_email', $request->input('token'))->first();

        if ($usuario == null) {
            throw new ModelNotFoundException();
        }

        $usuario->token_confirmar_email = null;
        $usuario->save();

        return response()->json(['mensagem' => 'Sua conta foi confirmada.']);
    }

    public function esqueciMinhaSenha(Request $request)
    {
        $this->validate($request, ['email' => 'required']);
        $usuario = Usuario::where('email', $request->input('email'))->first();

        if ($usuario == null) {
            throw new ModelNotFoundException();
        }

        $usuario->esqueciMinhaSenha();
        //Mail::to($usuario->email)->send(new RedefinirSenhaMail($usuario));
        dispatch(new RedefinirSenhaJob($usuario));

        return response()->json(['mensagem' => 'Enviamos um e-mail com instruções para redefinir sua senha.'], 200);
    }


    protected function respondWithToken($token)
    {
        return response()->json([
            'token' => $token
        ]);
    }
}
