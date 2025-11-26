(function () {
    // Wait for the editor + WP data to be ready.
    window.addEventListener('load', function () {
        if (!window.wp || !wp.data) {
            return;
        }

        const { select, dispatch } = wp.data;

        function syncTitleFromFields() {
            var givenField = document.getElementById('fah_given_names');
            var surnameField = document.getElementById('fah_surname');

            if (!givenField || !surnameField) {
                return;
            }

            var fullName = (givenField.value + ' ' + surnameField.value).trim();
            if (!fullName) {
                return;
            }

            var currentTitle = select('core/editor').getEditedPostAttribute('title');
            if (currentTitle !== fullName) {
                dispatch('core/editor').editPost({ title: fullName });
            }
        }

        // Sync once on load (useful when editing an existing person).
        syncTitleFromFields();

        // Sync whenever the given name or surname fields change.
        document.addEventListener('input', function (event) {
            if (event.target && (event.target.id === 'fah_given_names' || event.target.id === 'fah_surname')) {
                syncTitleFromFields();
            }
        });
    });
})();
