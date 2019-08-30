var $pairOfTeams = $('.custom-control-input');
    $pairOfTeams.click(function (event) {
        var $card = $(this).parentsUntil('.col');
        if (!$card.hasClass('text-white bg-danger')) {
            $card.addClass('text-white bg-danger');
       } else {
            $card.removeClass('text-white bg-danger');
        }
    });
