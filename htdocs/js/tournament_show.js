var $pairOfTeams = $('.custom-control-input');
    $pairOfTeams.click(function (event) {
        var $card = $(this).parentsUntil('.col');
        if (!$card.hasClass('text-white bg-danger')) {
            $card.addClass('text-white bg-danger');
       } else {
            $card.removeClass('text-white bg-danger');
        }
    });

jQuery(document).ready(function($){
    var url = document.location.href;
    new Clipboard('.schedule', {text: function(){ return url;}});
    $('.schedule').click(function(){alert('Cсылка успешно скопирована в буфер обмена.');});
});