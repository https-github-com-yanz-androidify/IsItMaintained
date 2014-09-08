$(function () {
    $('#search').submit(function (e) {
        e.preventDefault();

        $(this).find('.alert-warning').hide();
        var resultDiv = $(this).find('.result');
        resultDiv.empty()
            .hide();

        var repository = $(this).find('#search-input').val();

        if (repository.length === 0 || repository.indexOf('/') === -1) {
            $(this).find('.alert-warning').show();
            return;
        }

        resultDiv.append('<h4>' + repository + '</h4>')
            .append('<img src="/badge/' + repository + '.svg">')
            .show();
    });
});