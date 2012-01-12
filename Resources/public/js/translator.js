var translator = {
    module: function(name){
        var modules = {};
        
        return function(name){
            if(modules[name]){
                return modules[name];
            } else {
                return modules[name] = {
                    moduleName: name,
                    Views: {}
                }
            }
        }
    }
}

translator.module = translator.module();

(function(Domain){
    
    var Editor = translator.module('Editor');
    
    Domain.Model = Backbone.Model.extend({
    });
    
    Domain.View = Backbone.View.extend({
        el: 'div',
        
        table: {},
        
        events: {
            'click td.editable':  'editContent'
        },
        
        editContent: function(event){
            Editor.get().initOn(event.currentTarget);
        },
        
        save: function(cell){
            var id = cell.attr('id');
            var path = this.table[id];
            var domain = this.domain
            // We have to stop before the last step to keep using objects
            // So we keep working on references and modify the domain values
            for(var i = 0; i < path.length - 1; i++){
                domain = domain[path[i]];
            }
            domain[path[path.length - 1]] = cell.html()
            $.ajax({
                type: "POST",
                url: this.url,
                data: {'translations': this.domain},
                success: function(data) {
                }
            });
        },
        
        initialize: function(args){
            this.locales = [];
            this.url = args.url,
            this.domain = args.domain;
            for(var locale in args.domain){
                this.locales.push(locale);
            }
            this.model = new Domain.Model(args.domain);
            Editor.get().bind('editor:validate', this.save, this);
        },
        
        render: function(){
            var locale = this.locales[0];
            this.$('table thead').html('');
            this.$('table thead').append(ich.header_row({
                locales: this.locales
            }))
            var entries = this.model.get(locale).entries;
            this.$('table tbody').html('');
            this.recurs(entries, '', ['entries']);
        },
        
        recurs: function(entries, prefix, path){
            for(var entry in entries){
                if(typeof entries[entry] == "string"){
                    var p = _.clone(path);
                    p.push(entry);
                    this.renderFullRow(entries[entry], prefix, p);
                } else {
                    var p = _.clone(path);
                    p.push(entry);
                    var data = {
                        name: prefix + entry,
                        length: this.locales.length + 1
                    }
                    this.$('table tbody').append(ich.nested_key(data));
                    this.recurs(entries[entry], prefix+'|--', p);
                }
            }
        },
        
        renderFullRow: function(entry, prefix, path){
            var data = {
                key: null,
                translations: []
            };
            for(var loc in this.locales){
                var locale = this.locales[loc]
                var path_id = locale;
                var translations = this.model.get(locale);
                for(var step in path){
                    path_id += "-"+path[step];
                    // empty string if the index was not defined in this locale
                    translations = translations[path[step]] || '';
                    data.key = prefix + path[step];
                }
                data.translations.push({
                    id: path_id, 
                    string: translations
                });
                this.table[path_id] = [locale].concat(path);
            }
            this.$('table tbody').append(ich.full_row(data))
        }
    })
    
})(translator.module('Domain'));

(function(Editor){
    
    Editor.View = Backbone.View.extend({
        el: 'div#sg-editor',
        
        currentText: '',
        
        currentElement: null,
        
        events: {
            'click input.cancel': 'cancel',
            'click input.submit': 'validate'
        },
        
        cancel: function() {
            if(this.currentElement){
                this.currentElement.html(this.currentText);
                this.currentElement.addClass('editable')
            }
            $(this.el).hide();
        },
        
        validate: function(event){
            var newText = this.$('textarea').val();
            this.currentElement.html(newText);
            this.currentElement.addClass('editable');
            $('body').append(this.el);
            this.trigger('editor:validate', this.currentElement);
            this.currentText = '';
            this.currentElement = null;
            $(this.el).hide();
        },
        
        initialize: function(){
            $(this.el).hide();
        },
        
        show: function(){
            $(this.el).show();
        },
        
        initOn: function(element){
            this.cancel();
            this.currentElement = $(element);
            this.currentElement.removeClass('editable');
            this.currentText = this.currentElement.html();
            this.setText();
            this.currentElement.html(this.el)
            this.show();
            this.$('textarea').focus();
        },
        
        setText: function(){
            this.$('textarea').val(this.currentText);
        }
    })
    
    $(document).ready(initEditor)

    function initEditor(){
        var ed = new Editor.View();
        
        Editor.get = function(){
            return ed;
        }
    }
})(translator.module('Editor'))