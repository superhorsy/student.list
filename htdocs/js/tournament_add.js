$(document).ready(function () {
    var counter = 0;

    $("#addrow").on("click", function () {
        $(".player:last").clone().appendTo(".players");
        counter++;
    });

    $(".players").on("click", ".deleteRow", function (event) {
        $(this).closest(".player").remove();
        counter -= 1;
    });
});