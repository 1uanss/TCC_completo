@extends('app')
@section('content')
<div class="row">

    <div class="col-md-6">
        @if($sentRequests >= 13)
        <div class="alert alert-info" role="alert">
            <h4 class="alert-heading">COMPLETOU!</h4>
            <p>Parabéns {{ Auth::user()->name }}! Você já completou a primeira etapa do teste</p>
            <hr>
            <p class="mb-0">Você sera redirecionado para uma nova pagina, se caso nao ocorre o redicionamento automatico, favor clicar no btão abaixo! Aguarde...</p>
        </div>
        <a class="btn btn-primary" href="{{ route('etapa2') }}">INICIAR 2° TESTE</a>
        @else
        <p>Bem vindo(a), <b>{{ Auth::user()->name }}</b></p>
        <form id="form">
            <label for="password">Digite a senha:</label>
            <input type="password" id="password" name="password">
            <input type="submit" id="submitBtn" value="Entrar">
        </form>
        <p>Quantidade de solicitações enviadas: <span id="sent-requests">{{ $sentRequests }}</span></p>
        <p>Limite de solicitações: <span id="request-limit">50</span></p>
        <a class="btn btn-danger" href="{{ route('logout') }}">Deslogar</a>
        @endif
    </div>
</div>
<script>
    //Variável global que guarda os eventos do teclado
    window.onload = function () {
        let keyLogs = [];
        const sentRequestsElement = document.getElementById('sent-requests');
        const requestLimitElement = document.getElementById('request-limit');
        const submitBtn = document.getElementById('submitBtn'); // Adicione o ID ao botão
        
        // Eventos para capturar tempos de pressionamento e intervalos
        const passwordField = document.getElementById('password');
        const loginForm = document.getElementById('form');


        passwordField.addEventListener('keydown', function(event) {

            const keyCode = event.keyCode || event.which; // Para compatibilidade com navegadores antigos
            const key = event.key;

            let ts = new Date().getTime();

            //Se o caractere for imprimível
            if ((keyCode >= 32 && keyCode <= 126)) {

                let logItem = {'key': key, 'ts': ts, 'evt': 'down'};

                keyLogs.push (logItem);
            }

            
        });



        passwordField.addEventListener('keyup', function(event) {
  
            const keyCode = event.keyCode || event.which; // Para compatibilidade com navegadores antigos
            const key = event.key;

            let ts = new Date().getTime();

            //Se o caractere for imprimível
            if ((keyCode >= 32 && keyCode <= 126)) {

                let logItem = {'key': key, 'ts': ts, 'evt': 'up'};

                keyLogs.push (logItem);

            }

            //Se for algum caractere que atrapalha a digitação da senha, ela deve ser apagada
            if ( key === 'Backspace' || key === 'Delete' || key === 'Home' || key === 'End'  || key.includes('Arrow') ){
                clearPasswordInput();
            }

        });


        passwordField.addEventListener('click', function(event) {
            clearPasswordInput();
        });


        passwordField.addEventListener('focus', function(event) {
            clearPasswordInput();
        });



        loginForm.addEventListener('submit', function (event) {
            event.preventDefault();

            let password = getPassword(keyLogs);
            let pressTimes = getKeyPressTimes(keyLogs);
            let intervalTimes = getKeyIntervalTimes(keyLogs);
            let arrayTimes = getArrayTimes(keyLogs);

            let strPressTimes = JSON.stringify(pressTimes);
            let strIntervalTimes = JSON.stringify(intervalTimes);
            let strArrayTimes = JSON.stringify(arrayTimes);

            // Enviar dados para o backend Laravel usando AJAX
            fetch('/processar_dados_teclado', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    password: password,
                    press_times: pressTimes,
                    interval_times: intervalTimes,
                    array_times: arrayTimes
                })
            })
            .then(response => response.json())
            .then(data => {

                // Verificar se o backend indica redirecionamento
                if (data.redirect) {
                    alert(data.message); // Apenas um alerta para fins de teste; você pode personalizar conforme necessário
                    window.location.href = '/etapa2'; // Redirecionar para outra página
                    return;
                }

                // Atualizar a quantidade de solicitações no frontend
                if (sentRequestsElement && requestLimitElement) {
                    sentRequestsElement.textContent = data.sent_requests;
                    requestLimitElement.textContent = data.request_limit;

                    // Verificar se o limite foi atingido e habilitar/desabilitar o botão
                    if (data.sent_requests >= data.request_limit) {
                        submitBtn.disabled = true; // Desabilitar o botão
                        // Ou redirecione o usuário para outra página
                        // window.location.href = '/outra-pagina';
                    } else {
                        submitBtn.disabled = false; // Habilitar o botão
                    }
                }

                // Faça algo com a resposta do servidor, se necessário
                console.log(data);

                passwordField.value = '';
                keyLogs = []; // Limpar os logs após o envio
            })
            .catch(error => console.error('Erro ao enviar dados para o servidor:', error));

            return false;
        });

    }


    function clearPasswordInput () {
        keyLogs = [];
        document.getElementById('password').value = "";
    }



    function getKeyPressTimes (  keyLogs ){
        let r = [];
        for(var i = 0; i < keyLogs.length; i++){

            //Quando encontrar um evento de 'down'
            if (keyLogs[i]['evt'] == 'down' ){

                //Salvar a tecla
                key = keyLogs[i]['key']; 

                //E buscar o primeiro evento de 'up' daquela tecla
                for (var j = i+1; j < keyLogs.length; j++){
                    if (keyLogs[j]['key'] == key && keyLogs[j]['evt'] == 'up' ){
                        time = keyLogs[j]['ts'] - keyLogs[i]['ts'];
                        r.push(time);
                        break;
                    }
                }

            }
        }

        return r;

    }


    function getPassword (keyLogs) {
        let r = "";

        for(var i = 0; i < keyLogs.length; i++){
            if (keyLogs[i]['evt'] == 'down' ){
                r += keyLogs[i]['key'];
            }
        }

        return r;

    }


    function getKeyIntervalTimes ( keyLogs ) {


        //Fazer uma cópia de keyLogs
        keyLogsCpy = keyLogs.slice();

        let r = [];

        let password = getPassword(keyLogs);


        let lastTsDown = 0;
        let lastTSUp = 0;


        for (var i = 0; i < password.length; i++){

            let key = password[i];

            let tsDown = lastTsDown;
            let tsUp = lastTSUp;

            //Buscar os tempos de down do caractere de índice i em keyLogsCpy
            for (var j = 0; j < keyLogsCpy.length; j++){
                if (keyLogsCpy[j]['key'] == key && keyLogsCpy[j]['evt'] == 'down'){
                    tsDown = keyLogsCpy[j]['ts'];
                    //Remover o elemento de keyLogsCpy
                    keyLogsCpy.splice(j, 1);
                    break;
                }  
            }

            //Buscar os tempos de up do caractere de índice i em keyLogsCpy
            for (var j = 0; j < keyLogsCpy.length; j++){
                if (keyLogsCpy[j]['key'] == key && keyLogsCpy[j]['evt'] == 'up'){
                    tsUp = keyLogsCpy[j]['ts'];
                    //Remover o elemento de keyLogsCpy
                    keyLogsCpy.splice(j, 1);
                    break;
                }

            }

            if (i > 0){
                let interval = tsDown - lastTSUp;
                r.push(interval);
            }

            lastTsDown = tsDown;
            lastTSUp = tsUp;

        }


        return r;

    }

    function getArrayTimes ( keyLogs ) {
        let pressTimes = getKeyPressTimes(keyLogs);
        let intervalTimes = getKeyIntervalTimes(keyLogs);

        if (intervalTimes.length == 0 && pressTimes.length == 0)
            return [];

        if (intervalTimes.length != pressTimes.length - 1){
            throw new Error('Vetores de pressionamento e intervalos com tamanhos inconsistentes.'); 
        }

        //Intercalar pressTimes e intervalTimes
        let r = [];

        for (var i = 0; i < intervalTimes.length; i++){
            r.push( pressTimes[i] );
            r.push( intervalTimes[i] );
        }

        r.push ( pressTimes[  pressTimes.length - 1 ]  );

        return r;

    }
  </script>
@endsection