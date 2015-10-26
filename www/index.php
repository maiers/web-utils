<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
        <link rel="stylesheet" href="inc/bootstrap/css/bootstrap.min.css">
        <link rel="stylesheet" href="inc/bootstrap-tagsinput/bootstrap-tagsinput.css">
        <style>

            .bootstrap-tagsinput {
                width: 100%;
            }

        </style>
    </head>
    <body>
        <?php

          require_once('api/config.php');
        
        ?>

        <script>

        </script>

        <div class="container">

            <h1>Configs <small>from you config folder</small></h1>
            <form action="index.php" method="GET">
                <select id="config" name="config" class="form-control">
                    <?php $i = 0; foreach ($configs as $config) { 
                        $selected = ($config === $activeConfigPath) ? " selected='selected'" : "";
                    ?>
                    <option<?= $selected ?> value="<?= $i++ ?>"><?= $config ?></option>
                    <?php } ?>
                </select>
            </form>

            <h1>Keys <small>defined in language files and usage in code files</small></h1>
            <input class="form-control" name="key-filter" id="key-filter" placeholder="Filter keys">
            <table class="table table-striped" id="keys">
                <thead>
                    <tr>
                        <th>Key</th>
                        <th>Lanuages</th>
                        <th>Usage count</th>
                        <th>Replacement</th>
                    </tr>
                </thead>
                <tbody>

                </tbody>
            </table>

            <h1>Code files <small>selected and # keys referenced</small></h1>
            <input class="form-control" name="code-filter" placeholder="Filter by referenced keys" data-role="tagsinput" id="code-filter">
            <table class="table table-striped" id="code">
                <thead>
                    <tr>
                        <th>File</th>
                        <th>Keys</th>
                        <th>Replaced</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>

        </div>

        <script src="inc/jquery-2.1.4.min.js"></script>
        <script src="inc/d3.min.js"></script>
        <script src="inc/bootstrap/js/bootstrap.min.js"></script>
        <script src="inc/bootstrap-tagsinput/bootstrap-tagsinput.min.js"></script>
        <script>
          
            var keys = [], scanned = [], configId = <?= $activeConfigId ?>;

            /**
             * Event handling
             */
            $('#config').on('change', function () {
                $('#config').parent('form').submit();
            });

            $('#keys tbody').on('click', 'tr td:first-child', function () {
                var key = $(this).text();
                $('#code-filter').tagsinput(tagExists(key) ? 'remove' : 'add', key);
            });

            $('#code-filter').on('itemAdded', filterByTags);

            $('#code-filter').on('itemRemoved', filterByTags);

            $('#key-filter').on('keyup', function () {
                var filter = $('#key-filter').val();
                var filtered = keys.filter(function (d) {
                    return d.key.match(filter);
                });
                updateKeys(filtered);
            });

            /**
             * check if a tag exists
             * @param {type} tag
             * @returns {unresolved}
             */
            function tagExists(tag) {
                return $('#code-filter').tagsinput('items').find(function (a) {
                    return a === tag;
                });
            }

            /**
             * filter code list by tags, if any
             * 
             * @param {type} event
             * @returns {undefined}
             */
            function filterByTags(event) {
                var filtered = scanned.filter(function (d) {
                    return $('#code-filter').tagsinput('items').find(function (a) {
                        return Object.keys(d.keys).find(function (b) {
                            return b.match(a);
                        });
                    });
                });
                updateCode(filtered.length > 0 ? filtered : scanned);
            }

            /**
             * update key table, takes the array of keys and updates
             * the dom using d3.js
             * 
             * @param {type} keys
             * @returns {undefined}
             */
            function updateKeys(keys) {

                var rows = d3.select('#keys tbody').selectAll('tr').data(keys, function (d) {
                    return d.key;
                });

                rows.enter().append('tr').call(function (d) {
                    d.append('td').text(function (d) {
                        return d.key;
                    });
                    d.append('td').text(function (d) {
                        return d.langs.join(', ');
                    });
                    d.append('td').text(function (d) {
                        return d.usage;
                    });
                    d.append('td').text(function (d) {
                        return d.replacement;
                    });
                });

                rows.exit().remove();

            }

            /**
             * update code table, takes the array of files and updates
             * the dom using d3.js
             * 
             * @param {type} files
             * @returns {undefined}
             */
            function updateCode(files) {

                files.sort(function (a, b) {
                    return b.keys['_sum'] - a.keys['_sum'];
                });

                var rows = d3.select('#code tbody').selectAll('tr').data(files, function (d) {
                    return d.path;
                });

                rows.enter()
                        .append('tr')
                        .call(function (d) {
                            d.append('td').text(function (d) {
                                return d.path;
                            });
                            d.append('td').text(function (d) {
                                return d.keys['_sum'];
                            });
                            d.append('td').text(function (d) {
                                return d.replaced;
                            });
                        });

                rows.exit().remove();

            }

            // initially fill the tables
            updateCode(scanned);
            
            $.get('api/keys.php?config=' + configId).success(function (d) {
              keys = d;
              updateKeys(keys);
            });

        </script>

    </body>
</html>