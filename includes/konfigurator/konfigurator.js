jQuery(document).ready(function($) {
    var currentStep = 1;
    var totalSteps = 5;

    function showStep(step) {
        $('.step-content').removeClass('active').hide();
        $('.step-content[data-step="' + step + '"]').addClass('active').fadeIn();

        $('#progress-bar .step').removeClass('active');
        $('#progress-bar .step[data-step="' + step + '"]').addClass('active');

        if (step === 1) {
            $('#prev-btn').hide();
            $('#next-btn').show();
            $('#submit-btn').hide();
        } else if (step === totalSteps) {
            $('#prev-btn').show();
            $('#next-btn').hide();
            $('#submit-btn').show();
            updateSummary();
        } else {
            $('#prev-btn').show();
            $('#next-btn').show();
            $('#submit-btn').hide();
        }
    }

    $('#next-btn').on('click', function() {
        if (currentStep < totalSteps) {
            currentStep++;
            showStep(currentStep);
        }
    });

    $('#prev-btn').on('click', function() {
        if (currentStep > 1) {
            currentStep--;
            showStep(currentStep);
        }
    });

    function updateSummary() {
        var summaryHtml = '<ul>';
        var seria = $('input[name="seria"]:checked').val();
        summaryHtml += '<li><strong>Seria:</strong> ' + (seria ? seria : 'Nie wybrano') + '</li>';
        var ksztalt = $('input[name="ksztalt"]:checked').val();
        summaryHtml += '<li><strong>Kształt:</strong> ' + (ksztalt ? ksztalt : 'Nie wybrano') + '</li>';
        var uklad = $('input[name="uklad"]:checked').val();
        summaryHtml += '<li><strong>Układ:</strong> ' + (uklad ? uklad : 'Nie wybrano') + '</li>';
        var mechanizmy = [];
        $('input[name="mechanizmy[]"]:checked').each(function(){
            mechanizmy.push($(this).val());
        });
        summaryHtml += '<li><strong>Mechanizmy:</strong> ' + (mechanizmy.length ? mechanizmy.join(', ') : 'Nie wybrano') + '</li>';
        summaryHtml += '</ul>';
        $('#podsumowanie').html(summaryHtml);
    }

    showStep(currentStep);
});
