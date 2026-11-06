$(function () {
    'use strict';

    const $form = $('#consultaForm');
    const $error = $('#mensagemErro');
    const $time = $('#tempoCarregamento');
    const $button = $('#btConsultar');

    const table = $('#tabela').DataTable({
        data: [],
        pageLength: 10,
        order: [[3, 'desc']],
        language: {
            emptyTable: 'Nenhum ticket encontrado',
            info: 'Exibindo _START_ a _END_ de _TOTAL_ registros',
            infoEmpty: 'Nenhum registro',
            infoFiltered: '(filtrado de _MAX_)',
            lengthMenu: 'Mostrar _MENU_ registros',
            search: 'Pesquisar:',
            zeroRecords: 'Nenhum registro corresponde à pesquisa',
            paginate: { first: 'Primeiro', last: 'Último', next: 'Próximo', previous: 'Anterior' }
        },
        columns: [
            { data: 'ticket_id' },
            { data: 'titulo' },
            { data: 'descricao' },
            { data: 'data_criacao_id' },
            { data: 'tipo_servico_id' },
            { data: 'prioridade' },
            { data: 'status_chamado' }
        ],
        columnDefs: [
            { targets: 3, render: function (data, type, row) {
                if (type === 'sort' || type === 'type') return row.data_criacao_iso || '';
                return $.fn.dataTable.render.text().display(data || '');
            } },
            { targets: [0, 1, 2, 4, 5, 6], render: $.fn.dataTable.render.text() }
        ]
    });

    function atualizarDatas() {
        const todos = $('#todos').is(':checked');
        $('#dt_inicial, #dt_final').prop('disabled', todos);
        if (todos) $('#dt_inicial, #dt_final').val('');
    }
    $('#todos').on('change', atualizarDatas);
    $form.on('reset', function () {
        window.setTimeout(function () {
            atualizarDatas();
            $error.addClass('d-none').text('');
            $time.text('Selecione a consulta para visualizar resultados.');
            table.clear().draw();
        }, 0);
    });
    atualizarDatas();

    $form.on('submit', function (event) {
        event.preventDefault();
        $error.addClass('d-none').text('');
        const todos = $('#todos').is(':checked');
        const inicio = $('#dt_inicial').val();
        const fim = $('#dt_final').val();
        if (!todos && (!inicio || !fim || inicio > fim)) {
            $error.text('Informe um período válido ou selecione Todos os registros.').removeClass('d-none');
            return;
        }
        $button.prop('disabled', true);
        $time.text('Consultando...');
        const started = performance.now();
        $.ajax({
            url: 'consulta.php',
            type: 'POST',
            dataType: 'json',
            data: { dt_inicial: inicio, dt_final: fim, todos: todos ? '1' : '0', banco: $('#banco').val() }
        }).done(function (response) {
            if (response.error) {
                $error.text(response.error).removeClass('d-none');
                return;
            }
            table.clear().rows.add(response.data || []).draw();
            const total = Math.round(performance.now() - started);
            const server = Number(response.execution_time_ms || 0).toFixed(1);
            $time.text('Registros: ' + (response.data || []).length +
                ' | Consulta no servidor: ' + server + ' ms' +
                ' | Carregamento total no navegador: ' + total + ' ms');
        }).fail(function (xhr) {
            const msg = xhr.responseJSON && xhr.responseJSON.error
                ? xhr.responseJSON.error : 'Não foi possível executar a consulta.';
            $error.text(msg).removeClass('d-none');
            $time.text('Falha na consulta.');
        }).always(function () { $button.prop('disabled', false); });
    });
});
