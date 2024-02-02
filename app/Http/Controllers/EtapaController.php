<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;

use App\Models\KeyboardData;
use App\Models\User;

class EtapaController extends Controller
{
    private $user;
    private $sentRequests;
    private $currentWord;
    public $word;

    public function __construct()
    {
        $this->middleware('auth'); // Middleware de autenticação
    }

    public function index()
    {
        $data['title'] = 'ETAPA 1';
        $user = Auth::user();
        // Verificar a quantidade de solicitações já enviadas
        $sentRequests = $user->keyboardData()->count();
        return view('etapa/index', $data, ['sentRequests' => $sentRequests]);
    }

    public function index2()
    {
        $data['title'] = 'ETAPA 2';
        $user = Auth::user();

        // Verificar se a palavra atual já foi carregada
        if (!session()->has('currentWord')) {
            $this->loadNextWord(); // Carregar a próxima palavra
            session(['currentWord' => $this->currentWord]);
        }
        return view('etapa/index2', $data, ['currentWord' => session('currentWord')]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        // Verificar a quantidade de solicitações já enviadas
        $sentRequests = $user->keyboardData()->count();

        // Verificar se o usuário atingiu o limite
        if ($sentRequests >= 5) {
            return response()->json(['redirect' => true, 'message' => 'Limite de solicitações atingido.']);
        }

        // Validação dos dados recebidos do frontend
        $request->validate([
            'password' => 'required|string',
            'press_times' => 'required|array',
            'interval_times' => 'required|array',
            'array_times' => 'required|array',
        ]);

        // Crie uma instância do modelo e preencha com os dados
        $keyboardData = new KeyboardData;
        $keyboardData->user_id = Auth::user()->user_id;
        $keyboardData->password = $request->input('password');
        $keyboardData->press_times = json_encode($request->input('press_times'));
        $keyboardData->interval_times = json_encode($request->input('interval_times'));
        $keyboardData->array_times = json_encode($request->input('array_times'));
        $keyboardData->target_user_id = Auth::user()->user_id;

        // Salve no banco de dados
        $keyboardData->save();

        // Você pode retornar uma resposta JSON se necessário
        return response()->json([
            'message' => 'Dados salvos com sucesso',
            'sent_requests' => $sentRequests + 1,
            'request_limit' => 50
        ]);
    }

    public function loadNextWord()
    {
        $user = Auth::user();

        // Encontrar a próxima palavra não digitada associada a qualquer usuário (diferente do usuário atual)
        $word = User::where('user_id', '!=', $user->user_id)
            ->whereNotIn('user_id', function ($query) use ($user) {
                $query->select('target_user_id')
                    ->from('keyboard_data')
                    ->where('user_id', $user->user_id);
            })
            ->inRandomOrder()
            ->first();

        if ($word) {
            $this->currentWord = $word; // Assumindo que a senha está na coluna 'password_1'
        } else {
            $this->currentWord = null;
        }

    }

    public function store2(Request $request)
    {
        // Certifique-se de chamar loadNextWord antes de store2
        $this->currentWord = session('currentWord');
        
        $user = Auth::user();

        if ($this->currentWord->password_1 === $request->input('password1') && $this->currentWord->password_1 === $request->input('password2')) 
        {
            $targetUser = User::find($this->currentWord->user_id);

            // Validação dos dados recebidos do frontend
            $request->validate([
                'password1' => 'required|string',
                'press_times1' => 'required|array',
                'interval_times1' => 'required|array',
                'array_times1' => 'required|array',
                'password2' => 'required|string',
                'press_times2' => 'required|array',
                'interval_times2' => 'required|array',
                'array_times2' => 'required|array',
            ]);

            // Crie uma instância do modelo e preencha com os dados

            KeyboardData::create([
                'user_id' => Auth::user()->user_id,
                'password' => $request->input('password1'),
                'press_times' => json_encode($request->input('press_times1')),
                'interval_times' => json_encode($request->input('interval_times1')),
                'array_times' => json_encode($request->input('array_times1')),
                'target_user_id' => $targetUser->user_id,
            ]);

            KeyboardData::create([
                'user_id' => Auth::user()->user_id,
                'password' => $request->input('password2'),
                'press_times' => json_encode($request->input('press_times2')),
                'interval_times' => json_encode($request->input('interval_times2')),
                'array_times' => json_encode($request->input('array_times2')),
                'target_user_id' => $targetUser->user_id,
            ]);

            // Você pode retornar uma resposta JSON se necessário
            $this->loadNextWord();
            session(['currentWord' => $this->currentWord]);
            
            return response()->json([
                'message' => 'Dados salvos com sucesso',
                'currentWord' => $this->currentWord ? $this->currentWord->password_1 : null,
            ]);
        }else{
            return response()->json([
                'error' => 'As senhas não está batendo com a palavra atual.',
            ]);
        }
    }

    public function gerarRelatorio()
    {
        $user = Auth::user();

        // Processar registros para gerar o conteúdo do relatório
        $registros = KeyboardData::where('user_id', $user->user_id)
            ->where('target_user_id', $user->user_id)
            ->orWhere('password', $user->password_1)
            ->get();

        // Organizar os registros por data em ordem decrescente
        $registrosOrdenados = $this->organizarPorDataDescendente($registros);

        // Processar registros ordenados para gerar o conteúdo do relatório
        $relatorio = "Relatório de Digitação (Ordem Decrescente por Data):\n\n";

        foreach ($registrosOrdenados as $registro) {
            $relatorio .= "{$registro->array_times}\n";
        }

        // Criar o arquivo de relatório
        $nomeArquivo = 'relatorio_digitacao.txt';
        $caminhoArquivo = storage_path('app/public/' . $nomeArquivo);

        file_put_contents($caminhoArquivo, $relatorio);

        // Criar uma resposta para download
        return Response::download($caminhoArquivo, $nomeArquivo)->deleteFileAfterSend(true);
    }

    private function organizarPorDataDescendente(Collection $registros)
    {
        return $registros->sortBy(function ($registro) {
            return strtotime($registro->created_at);
        });
    }
}
