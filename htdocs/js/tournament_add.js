var Tournament = {
    init: function () {
        this.counter = 0;
        this.player = $(".player:last").clone().find("input").attr('value', '').attr('name', 'p_nickname[]').end();

        this.deletePlayerRow();
        this.addRow();
        this.addRegions();
        this.showRegionFiled();
    },
    deletePlayerRow: function () {
        $(".players").on("click", ".deleteRow", function (event) {
            $(this).closest(".player").remove();
            Tournament.counter -= 1;
        });
    },
    addRow: function () {
        $("#addrow").on("click", function () {
            Tournament.player.clone().appendTo(".players");
            Tournament.counter++;
        });
    },
    addRegions: function () {
        $('#t_regions').on("blur", function (e) {
            cities = $(e).val().trim().split(',');
            if (Array.isArray(cities) && cities.length) {
                for (let city of cities) {
                    $('<option>, { value:city, text:city}').appendTo($('#p_region'));
                }
            }
        });
    },
    showRegionFiled: function() {
        this.toogleRegionField();
        $('#t_type').on("change",function (e) {
            Tournament.toogleRegionField();
        });

    },
    toogleRegionField: function(){
        type = +$('#t_type').children('option:selected').val();
        if (type === 2) {
            $('#regions_row,.p_region').show();
        } else {
            $('#regions_row,.p_region').hide();
        }
    },
};
$(document).ready(function () {
    Tournament.init();
});