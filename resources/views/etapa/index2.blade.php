@extends('app')
@section('content')
<div class="row">

    <div class="col-md-6">

        <p>Bem vindo(a), <b>{{ Auth::user()->name }}</b></p>
        @if($currentWord)
        <form id="form">
            <div id="errorDisplay"></div>

            <label for="currentWordDisplay">Palavra Atual:</label>
            <input type="text" readonly class="form-control-plaintext" id="currentWordDisplay" value="{{ $currentWord->password_1 }}">
            <label for="password1">Digite a senha:</label>
            <input type="password" id="password1" name="password1" required>
            <label for="password2">Senha 2:</label>
            <input type="password" id="password2" name="password2" required>
            <input type="submit" id="submitBtn" value="Entrar">
        </form>
        @else
        <div class="alert alert-success" role="alert">
            <h4 class="alert-heading">Parabéns!</h4>
            <p>Você concluiu a 2° etapa do teste de digitação. Em seguida, você irá gerar um relatorio de digitação vinculada a sua palavra e as palavras dos usuários que você digitou.</p>
            <hr>
            <a class="btn btn-primary" href="{{ route('gerarRelatorio') }}">GERAR RELATORIO</a>
        </div>
        @endif
        <a class="btn btn-danger" href="{{ route('logout') }}">Deslogar</a>
        
    </div>
</div>
<script>
    //Variável global que guarda os eventos do teclado
    window.onload = function () {
        let keyLogs1 = [];
        let keyLogs2 = [];
        const sentRequestsElement = document.getElementById('sent-requests');
        const requestLimitElement = document.getElementById('request-limit');
        const submitBtn = document.getElementById('submitBtn'); // Adicione o ID ao botão
        
        // Eventos para capturar tempos de pressionamento e intervalos
        const passwordField1 = document.getElementById('password1');
        const passwordField2 = document.getElementById('password2');

        const currentWordDisplay = document.getElementById('currentWordDisplay');
        const errorDisplay = document.getElementById('errorDisplay');
        const loginForm = document.getElementById('form');

        function showAlert(message, alertType) {
            // Remover alertas existentes
            const existingAlerts = document.querySelectorAll('.custom-alert');
            existingAlerts.forEach(alert => alert.remove());

            // Criar elemento de alerta
            const alertElement = document.createElement('div');
            alertElement.classList.add('alert', 'alert-dismissible', 'fade', 'show', 'custom-alert', alertType);
            alertElement.innerHTML = `
                ${message}
            `;

            // Inserir o elemento de alerta antes do errorDisplay
            const errorDisplay = document.getElementById('errorDisplay');
            errorDisplay.insertAdjacentElement('beforebegin', alertElement);
        }

        function displayError(message) {
            const errorDisplay = document.getElementById('errorDisplay');
            if (errorDisplay) {
                errorDisplay.textContent = message;
            }
        }

        passwordField1.addEventListener('keydown', function(event) {

            const keyCode = event.keyCode || event.which; // Para compatibilidade com navegadores antigos
            const key = event.key;

            let ts = new Date().getTime();

            //Se o caractere for imprimível
            if ((keyCode >= 32 && keyCode <= 126)) {

                let logItem = {'key': key, 'ts': ts, 'evt': 'down'};

                keyLogs1.push (logItem);
            }

            
        });



        passwordField1.addEventListener('keyup', function(event) {
  
            const keyCode = event.keyCode || event.which; // Para compatibilidade com navegadores antigos
            const key = event.key;

            let ts = new Date().getTime();

            //Se o caractere for imprimível
            if ((keyCode >= 32 && keyCode <= 126)) {

                let logItem = {'key': key, 'ts': ts, 'evt': 'up'};

                keyLogs1.push (logItem);

            }

            //Se for algum caractere que atrapalha a digitação da senha, ela deve ser apagada
            if ( key === 'Backspace' || key === 'Delete' || key === 'Home' || key === 'End'  || key.includes('Arrow') ){
                clearPasswordInput();
            }

        });



        passwordField2.addEventListener('keydown', function(event) {

            const keyCode = event.keyCode || event.which; // Para compatibilidade com navegadores antigos
            const key = event.key;

            let ts = new Date().getTime();

            //Se o caractere for imprimível
            if ((keyCode >= 32 && keyCode <= 126)) {

                let logItem = {'key': key, 'ts': ts, 'evt': 'down'};

                keyLogs2.push (logItem);
            }


        });



        passwordField2.addEventListener('keyup', function(event) {

            const keyCode = event.keyCode || event.which; // Para compatibilidade com navegadores antigos
            const key = event.key;

            let ts = new Date().getTime();

            //Se o caractere for imprimível
            if ((keyCode >= 32 && keyCode <= 126)) {

                let logItem = {'key': key, 'ts': ts, 'evt': 'up'};

                keyLogs2.push (logItem);

            }

            //Se for algum caractere que atrapalha a digitação da senha, ela deve ser apagada
            if ( key === 'Backspace' || key === 'Delete' || key === 'Home' || key === 'End'  || key.includes('Arrow') ){
                clearPasswordInput();
            }

        });

        function updateCurrentWordDisplay(newWord) {
            if (currentWordDisplay) {
                currentWordDisplay.value = newWord;
            }
        }

        function reloadPage() {
            location.reload();
        }
        
        loginForm.addEventListener('submit', function (event) {
            event.preventDefault();

            errorDisplay.textContent = '';

            let password1 = getPassword(keyLogs1);
            let pressTimes1 = getKeyPressTimes(keyLogs1);
            let intervalTimes1 = getKeyIntervalTimes(keyLogs1);
            let arrayTimes1 = getArrayTimes(keyLogs1);

            let password2 = getPassword(keyLogs2);
            let pressTimes2 = getKeyPressTimes2(keyLogs2);
            let intervalTimes2 = getKeyIntervalTimes2(keyLogs2);
            let arrayTimes2 = getArrayTimes2(keyLogs2);

            // Enviar dados para o backend Laravel usando AJAX
            fetch('/processar_dados_teclado2', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    password1: password1,
                    press_times1: pressTimes1,
                    interval_times1: intervalTimes1,
                    array_times1: arrayTimes1,
                    password2: password2,
                    press_times2: pressTimes2,
                    interval_times2: intervalTimes2,
                    array_times2: arrayTimes2
                })
            })
            .then(response => response.json())
            .then(data => {

                // Verificar se o backend indica redirecionamento
                if (data.redirect) {
                    showAlert(data.message, 'alert-success');
                    window.location.href = '/outra-pagina'; // Redirecionar para outra página
                    return;
                }

                if (data.error) {
                    // Se houver um erro, exiba a mensagem de erro usando o alerta do Bootstrap
                    showAlert(data.error, 'alert-danger');
                } else if (data.currentWord !== null) {
                    // Se houver uma nova palavra, atualize o display
                    updateCurrentWordDisplay(data.currentWord);
                } else {
                    // Se não houver mais palavras, recarregue a página
                    reloadPage();
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

                keyLogs1 = [];
                keyLogs2 = [];

                passwordField1.value = '';
                passwordField2.value = '';
            })
            .catch(error => {
        console.error('Erro ao enviar dados para o servidor:', error);
        // Exibir mensagem de erro genérica, se necessário
        displayError('Erro ao processar a solicitação.');
    });

            return false;
        });

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

    function getKeyPressTimes2 (  keyLogs2 ){
        let r = [];
        for(var i = 0; i < keyLogs2.length; i++){

            //Quando encontrar um evento de 'down'
            if (keyLogs2[i]['evt'] == 'down' ){

                //Salvar a tecla
                key = keyLogs2[i]['key']; 

                //E buscar o primeiro evento de 'up' daquela tecla
                for (var j = i+1; j < keyLogs2.length; j++){
                    if (keyLogs2[j]['key'] == key && keyLogs2[j]['evt'] == 'up' ){
                        time = keyLogs2[j]['ts'] - keyLogs2[i]['ts'];
                        r.push(time);
                        break;
                    }
                }

            }
        }

        return r;

    }


    function getPassword2 (keyLogs2) {
        let r = "";

        for(var i = 0; i < keyLogs2.length; i++){
            if (keyLogs2[i]['evt'] == 'down' ){
                r += keyLogs2[i]['key'];
            }
        }

        return r;

    }


    function getKeyIntervalTimes2 ( keyLogs2 ) {


        //Fazer uma cópia de keyLogs
        keyLogsCpy = keyLogs2.slice();

        let r = [];

        let password = getPassword2(keyLogs2);


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

    function getArrayTimes2 ( keyLogs2 ) {
        let pressTimes2 = getKeyPressTimes2(keyLogs2);
        let intervalTimes2 = getKeyIntervalTimes2(keyLogs2);

        if (intervalTimes2.length == 0 && pressTimes2.length == 0)
            return [];

        if (intervalTimes2.length != pressTimes2.length - 1){
            throw new Error('Vetores de pressionamento e intervalos com tamanhos inconsistentes.'); 
        }

        //Intercalar pressTimes e intervalTimes
        let r = [];

        for (var i = 0; i < intervalTimes2.length; i++){
            r.push( pressTimes2[i] );
            r.push( intervalTimes2[i] );
        }

        r.push ( pressTimes2[  pressTimes2.length - 1 ]  );

        return r;

    }
</script>
@endsection