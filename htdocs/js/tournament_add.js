var Tournament = {
    init: function () {
        this.counter = 0;
        this.player = $(".player:last").clone()
            .find("input")
            .attr({value: ''})
            .end();

        this.deletePlayerRow();
        this.addPlayerRow();
        this.addRegions();
        this.showRegionFiled();
    },
    deletePlayerRow: function () {
        $(".players").on("click", ".deleteRow", function (event) {
            $(this).closest(".player").remove();
            Tournament.counter -= 1;
        });
    },
    addPlayerRow: function () {
        $("#addrow").on("click", function () {
            Tournament.player
                .clone()
                .find("name").each(function () {
                $(this).attr("name", $(this).attr("name").replace(/(\d+)/, function (match, number) {
                    return parseInt(number) + 1;
                }));
            })
                .appendTo(".players");
            Tournament.counter++;
            Tournament.toogleRegionField();
            Tournament.updateRegion();
        });

    },
    addRegions: function () {
        $('#t_regions').on("change", function (e) {
            Tournament.updateRegion();
        });
    },
    updateRegion: function () {
        $('.new_option').remove();
        cities = $('#t_regions').val().trim().split(',');
        if (Array.isArray(cities) && cities.length) {
            for (let city of cities) {
                if (city.length > 0) {
                    $('.p_region select').append("<option value='" + city + "' class='new_option'>" + city + "</option>");
                }
            }
        }
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
            $('.p_region > select').attr('disabled', false);
        } else {
            $('#regions_row,.p_region').hide();
            $('.p_region > select').attr('disabled', true);
        }
    },
};
$(document).ready(function () {
    Tournament.init();
});