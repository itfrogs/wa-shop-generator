$.extend($.importexport.plugins, {
    generator: {
        $form: null,
        progress: false,
        ajax_pull: {},
        data: {
            params: {}
        },
        debug: {
            'memory': 0.0,
            'memory_avg': 0.0
        },

        init: function (data) {
            $.shop.trace('init data', data);
            this.$form = $('#s-plugin-generator');
            $.extend(this.data, data);
        },

        action: function () {
        },

        onInit: function () {
            $.importexport.products.init(this.$form);

            this.$form.unbind('submit.generator').bind('submit.generator', function (evt) {
                $.shop.trace('submit.generator ' + evt.namespace, evt);
                $.importexport.plugins.generator.generatorHandler(this);
                return false;
            });
        },

        actionHandler: function (elm) {

            $.shop.trace('actionHadler arg', elm);
            //$.shop.trace('actionHadler args', args);
            //$.shop.trace('actionHandler getMethod')

            return false;
        },

        select: function(e, category_id) {
            var name = $(e).find('span.name').text();
            $('#s-plugin-generator-cat').val(name);
            $('#s-plugin-generator-catid').val(category_id);
        },

        generatorHandler: function (elm) {
            var self = this;
            self.progress = true;
            self.form = $(elm);
            var data = self.form.serialize();
            var url = $(elm).attr('action');
            $.shop.trace('elm', elm);
            self.form.find('.errormsg').text('');
            self.form.find(':input').prop('disabled', true);
            self.form.find(':submit').hide();
            self.form.find('.progressbar .progressbar-inner').css('width', '0');
            self.form.find('.progressbar').show();
            self.form.find('#plugin-generator-report').hide();

            $.ajax({
                url: url,
                data: data,
                dataType: 'json',
                type: 'post',
                success: function(response){
                    if(response.error) {
                        self.form.find(':input').prop('disabled', false);
                        self.form.find(':submit').show();
                        self.form.find('.js-progressbar-container').hide();
                        self.form.find('.shop-ajax-status-loading').remove();
                        self.progress = false;
                        self.form.find('.errormsg').text(response.error);
                    } else {
                        self.form.find('.progressbar').attr('title', '0.00%');
                        self.form.find('.progressbar-description').text('0.00%');
                        self.form.find('.js-progressbar-container').show();

                        self.ajax_pull[response.processId] = [];
                        self.ajax_pull[response.processId].push(setTimeout(function(){
                            $.wa.errorHandler = function(xhr){
                                return !((xhr.status >= 500) || (xhr.status == 0))
                            };
                            self.progressHandler(url, response.processId, response);
                        }, 100));
                        self.ajax_pull[response.processId].push(setTimeout(function () {
                            self.progressHandler(url, response.processId, null);
                        }, 2000));
                    }
                },
                error: function () {
                    self.form.find(':input').attr('disabled', false);
                    self.form.find(':submit').show();
                    self.form.find('.js-progressbar-container').hide();
                    self.form.find('.shop-ajax-status-loading').remove();
                    self.form.find('.progressbar').hide();
                }
            });
        },

        progressHandler: function (url, processId, response) {
            // display progress
            // if not completed do next iteration
            var self = $.importexport.plugins.generator;
            var $bar;
            if (response && response.ready) {
                $.wa.errorHandler = null;
                var timer;
                while (timer = self.ajax_pull[processId].pop()) {
                    if (timer) {
                        clearTimeout(timer);
                    }
                }
                $bar = self.form.find('.progressbar .progressbar-inner');
                $bar.css({
                    'width': '100%'
                });
                $.shop.trace('cleanup', response.processId);


                $.ajax({
                    url: url,
                    data: {
                        'processId': response.processId,
                        'cleanup': 1
                    },
                    dataType: 'json',
                    type: 'post',
                    success: function (response) {
                        $.shop.trace('report', response);
                        self.form.find('.js-progressbar-container').hide();
                        var $report = $("#plugin-generator-report");
                        $report.show();
                        if (response.report) {
                            $report.find(".value:first").html(response.report);
                        }
                        self.form.find(':input').prop('disabled', false);
                        self.form.find(':submit').show();
                        //$.storage.del('shop/hash');
                    }
                });

            } else if (response && response.error) {

                self.form.find(':input').attr('disabled', false);
                self.form.find(':submit').show();
                self.form.find('.js-progressbar-container').hide();
                self.form.find('.shop-ajax-status-loading').remove();
                self.form.find('.progressbar').hide();
                self.form.find('.errormsg').text(response.error);

            } else {
                var $description;
                if (response && (typeof(response.progress) != 'undefined')) {
                    $bar = self.form.find('.progressbar .progressbar-inner');
                    var progress = parseFloat(response.progress.replace(/,/, '.'));
                    $bar.animate({
                        'width': progress + '%'
                    });
                    self.debug.memory = Math.max(0.0, self.debug.memory, parseFloat(response.memory) || 0);
                    self.debug.memory_avg = Math.max(0.0, self.debug.memory_avg, parseFloat(response.memory_avg) || 0);

                    var title = 'Memory usage: ' + self.debug.memory_avg + '/' + self.debug.memory + 'MB';
                    title += ' (' + (1 + response.stage_num) + '/' + (0 + response.stage_count) + ')';

                    //var message = response.progress + ' — ' + response.stage_name;
                    var message = response.progress;

                    $bar.parents('.progressbar').attr('title', response.progress);
                    $description = self.form.find('.progressbar-description');
                    $description.text(message);
                    $description.attr('title', title);
                }
                if (response && (typeof(response.warning) != 'undefined')) {
                    $description = self.form.find('.progressbar-description');
                    $description.append('<i class="icon16 exclamation"></i><p>' + response.warning + '</p>');
                }

                var ajax_url = url;
                var id = processId;

                self.ajax_pull[id].push(setTimeout(function () {
                    $.ajax({
                        url: ajax_url,
                        data: {
                            'processId': id
                        },
                        dataType: 'json',
                        type: 'post',
                        success: function (response) {
                            self.progressHandler(url, response ? response.processId || id : id, response);
                        },
                        error: function () {
                            self.progressHandler(url, id, null);
                        }
                    });
                }, 1000));
            }
        }
    }
});