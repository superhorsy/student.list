$(document).ready(function () {
    var counter = 0;
    var player = $(".player:last").clone().find("input").attr('value','').attr('name','p_nickname[]').end();

    $("#addrow").on("click", function () {
        player.clone().appendTo(".players");
        counter++;
    });

    $(".players").on("click", ".deleteRow", function (event)    {
        $(this).closest(".player").remove();
        counter -= 1;
    });
});