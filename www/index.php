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
        
        require_once('../vendor/autoload.php');
        
        // handle config
        $configPath = "config";
        $configs = glob("$configPath/*.yml", GLOB_BRACE);
        $defaultConfigPath = "$configPath/default.yml";
        $activeConfigPath = (array_key_exists("config", $_POST)) ? filter_input(INPUT_POST, "config", FILTER_SANITIZE_STRING) : $defaultConfigPath;
        
        if (!file_exists($activeConfigPath)) {
            throw new Exception("Config path not set");
        }
        
        // load config
        $activeConfig = Spyc::YAMLLoad($activeConfigPath);
        
        // set variables
        $languageFiles = $activeConfig["languageFiles"]["path"];
        $languageFilePattern = $activeConfig["languageFiles"]["pattern"];
        $scanFolders = $activeConfig["codeFiles"]["paths"];
        $scanIncludePatterns = $activeConfig["codeFiles"]["include"];
        $scanExcludePatterns = $activeConfig["codeFiles"]["exclude"];
        $lineDelimiter = $activeConfig["lineEnding"];
        $keyReplacement = $activeConfig["keys"]["replace"];

        // read keys from language files
        $langFiles = scandir($languageFiles);
        $keys = [];
        foreach ($langFiles as $file) {
            $matches = null;
            if (preg_match($languageFilePattern, $file, $matches)) {
                $translation = parse_ini_file("$languageFiles/$file");
                foreach ($translation as $key => $value) {
                    if (strlen(trim($key)) === 0)
                        continue;
                    if (!array_key_exists($key, $keys)) {
                        $keys[$key] = ["langs" => [], "usage" => 0, "replacement" => []];
                    }
                    $keys[$key]["langs"][] = $matches[1];
                    if (array_key_exists($key, $keyReplacement)) {
                        $keys[$key]["replacement"] = $keyReplacement[$key];
                    }
                }
            }
        }

        // get list of files
        $codeFiles = [];
        foreach ($scanFolders as $folder) {
            $codeFiles = array_merge($codeFiles, dirToArray($folder, $scanIncludePatterns, $scanExcludePatterns));
        }

        // scan files for key usage
        $scanned = [];
        foreach ($codeFiles as $file) {
            $result = ["path" => $file, "replaced" => 0, "keys" => ["_sum" => 0]];
            foreach ($keys as $key => $value) {
                $contents = file_get_contents($file);
                $lines = explode($lineDelimiter, $contents);
                for ($i = 0; $i < count($lines); $i++) {
                    $line = $lines[$i];
                    $count = 0;
                    if (($count = preg_match_all("/$key/", $line)) > 0) {
                        $firstLine = max(0, $i - 1);
                        $context = array_slice($lines, $firstLine, 3);
                        $context = implode($lineDelimiter, $context);
                        $context = htmlspecialchars($context);
                        //echo "$line<br>$i<br>$file<br>$key<br>$context<hr>";
                        $keys[$key]["usage"] += $count;
                        $result["keys"]["_sum"] += $count;
                        if (!array_key_exists($key, $result["keys"])) {
                            $result["keys"][$key] = 0;
                        }
                        $result["keys"][$key] += $count;
                        // key is to be replace
                        if (array_key_exists($key, $keyReplacement)) {
                            preg_replace_callback("/$key/", function() {
                                global $key, $result, $keyReplacement;
                                $result["replaced"] ++;
                                return $keyReplacement[$key];
                            }, $line);
                        }
                    }
                }
            }
            $scanned[] = $result;
        }

        /**
         * Create a flat array of all files matching the provided patterns
         * within the provided directory and all of its sub-directories using
         * recursion.
         * 
         * Will remove all files matching any of the exclude patterns. Will
         * only include those files matching at least one of the include 
         * patterns.
         * 
         * Patterns are provided as arrays of regex string patterns.
         * 
         * @param string $dir to start with
         * @param array $includePatterns
         * @param array $excludePatterns
         * @return array
         */
        function dirToArray($dir, $includePatterns, $excludePatterns) {
            $out = [];
            $files = scandir($dir);
            foreach ($files as $file) {
                if (!in_array($file, array(".", ".."))) {
                    $sub = "$dir/$file";
                    //echo $sub . "<br>";
                    if (is_dir($sub)) {
                        $out = array_merge($out, dirToArray($sub, $includePatterns, $excludePatterns));
                    } else {
                        $exclude = false;
                        foreach ($excludePatterns as $pattern) {
                            if (preg_match($pattern, $sub)) {
                                $exclude = true;
                                break;
                            }
                        }
                        if (!$exclude) {
                            foreach ($includePatterns as $pattern) {
                                if (preg_match($pattern, $sub)) {
                                    $out[] = $sub;
                                    break;
                                }
                            }
                        }
                    }
                }
            }
            return $out;
        }
        ?>

        <script>

            <?php
            
                // create a non assoziative copy of the keys array
                $flatKeys = array_map(function($key, $value) {
                    $value["key"] = $key;
                    return $value;
                }, array_keys($keys), $keys);
            
            ?>

            var keys = <?= json_encode($flatKeys, JSON_PRETTY_PRINT) ?>;

            var scanned = <?= json_encode($scanned, JSON_PRETTY_PRINT) ?>;

        </script>

        <div class="container">

            <h1>Configs <small>from you config folder</small></h1>
            <form action="index.php" method="POST">
                <select id="config" name="config" class="form-control">
                    <?php foreach ($configs as $config) { 
                        $selected = ($config === $activeConfigPath) ? " selected='selected'" : "";
                    ?>
                    <option<?= $selected ?>><?= $config ?></option>
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
            updateKeys(keys);


        </script>

    </body>
</html>