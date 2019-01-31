var zipElement = $('#zipcode'),
    countryElement = $('#country');
countryElement.on('change', function () {
    var countryId = parseInt(countryElement.val()),
        countryName = countryElement.find("option:selected").text();

    var configEl = $('*[data-sheZipCodeCountryId="' + countryId +'"]');

    console.log(countryName, configEl);

    if (configEl.length) {
        zipElement.attr('pattern', configEl.data('pattern'));
        zipElement.attr('title', configEl.data('title').replace('{countryName}', countryName));
    } else {
        zipElement.attr('pattern', null);
        zipElement.attr('title', null);
    }
});
countryElement.trigger('change');


