(function() {
    tinymce.PluginManager.add( 'ezfc_tinymce', function( editor, url ) {
        var ezfc_menu = [];
        for (f in ezfc_forms) {
            ezfc_menu.push({
                form_id: ezfc_forms[f].id,
                text: "ID: " + ezfc_forms[f].id + " - " + ezfc_forms[f].name,
                onclick: function() {
                    editor.insertContent("[ezfc id='" + this.settings.form_id + "' /]");
                }
            });
        }

        // Add a button that opens a window
        editor.addButton( 'ezfc_tinymce_button', {
            text: 'EZFC Forms',
            icon: false,
            type: 'menubutton',
            menu: ezfc_menu
        });
    });
})();