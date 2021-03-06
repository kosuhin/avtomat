<?php

use Avtomat\Api\Avtomat;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$algoName = 'TestAlgo.json';
$runResult = 'none';
$inputJson = '[]';
define('ALGO_ROOT', '../test/algorithms/');

if (!isset($assetsDir)) {
    $assetsDir = '';
}

$editEnable = (isset($editEnable) and $editEnable);

if ($_GET) {
    if (isset($_GET['algorithm_name'])) {
        $algoName = $_GET['algorithm_name'];
    }

    if (isset($_GET['action'])) {
        $action = $_GET['action'];

        if ($editEnable) {
            if ($_POST && $action === 'run') {
                $inputJson = $_POST['input_json'];

                ob_start();
                $result = Avtomat::run($algoName, json_decode($inputJson));
                echo 'Результат выполнения: ' . json_encode($result);
                $runResult = ob_get_clean();
            }

            if ($_POST && $action === 'save') {
                Avtomat::saveAlgoFromGOJS($_POST['algorithm_json'], $algoName);
                header("Refresh:0");
            }
        }

    }
}

$objects = \Avtomat\Api\Avtomat::getAvailableObjects();

try {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Avtomat redactor</title>
        <meta name="description"
              content="Directed acyclic graph of nodes with varying input and output ports with labels, oriented horizontally."/>
        <meta charset="UTF-8">
        <script src="<?= $assetsDir ?>assets/lib/go.js"></script>
        <script src="<?= $assetsDir ?>assets/lib/jquery.min.js"></script>
        <script src="<?= $assetsDir ?>assets/lib/underscore-min.js"></script>
        <script src="<?= $assetsDir ?>assets/lib/vue.min.js"></script>
        <link rel="stylesheet" href="<?= $assetsDir ?>assets/main.css">
        <script src="<?= $assetsDir ?>assets/functions.js"></script>
        <script id="code">
            var myDiagramLink;

            function init() {
                var $ = go.GraphObject.make;

                myDiagram =
                    $(go.Diagram, "myDiagramDiv",
                        {
                            initialContentAlignment: go.Spot.Left,
                            initialAutoScale: go.Diagram.UniformToFill,
                            layout: $(go.LayeredDigraphLayout,
                                {direction: 0}),
                            "undoManager.isEnabled": true
                        }
                    );

                myDiagramLink = myDiagram;
                myDiagram.addDiagramListener("Modified", function (e) {
                    var button = document.getElementById("SaveButton");
                    if (button) button.disabled = !myDiagram.isModified;
                    var idx = document.title.indexOf("*");
                    if (myDiagram.isModified) {
                        if (idx < 0) document.title += "*";
                    } else {
                        if (idx >= 0) document.title = document.title.substr(0, idx);
                    }
                });

                function makeTemplate(typename, argument, background, inports, outports) {
                    var node = $(go.Node, "Spot",
                        $(go.Panel, "Auto",
                            {width: 160, height: 120},
                            $(go.Shape, "Rectangle",
                                {
                                    fill: background, stroke: null, strokeWidth: 0,
                                    spot1: go.Spot.TopLeft, spot2: go.Spot.BottomRight
                                }),
                            $(go.Panel, "Table",
                                $(go.TextBlock, typename,
                                    {
                                        row: 0,
                                        margin: 3,
                                        maxSize: new go.Size(80, NaN),
                                        stroke: "white",
                                        font: "bold 11pt sans-serif"
                                    }),
                                $(go.Picture, '',
                                    {row: 1, width: 35, height: 35}),
                                $(go.TextBlock,
                                    {
                                        row: 2,
                                        margin: 3,
                                        editable: true,
                                        maxSize: new go.Size(100, 40),
                                        stroke: "white",
                                        font: "bold 9pt sans-serif"
                                    },
                                    new go.Binding("text", "name").makeTwoWay())
                            )
                        ),
                        $(go.Panel, "Vertical",
                            {
                                alignment: go.Spot.Left,
                                alignmentFocus: new go.Spot(0, 0.5, -8, 0)
                            },
                            inports),
                        $(go.Panel, "Vertical",
                            {
                                alignment: go.Spot.Right,
                                alignmentFocus: new go.Spot(1, 0.5, 8, 0)
                            },
                            outports)
                    );
                    myDiagram.nodeTemplateMap.add(typename, node);
                }

                function makePort(name, leftside) {
                    var port = $(go.Shape, "Rectangle",
                        {
                            fill: "gray", stroke: null,
                            desiredSize: new go.Size(8, 8),
                            portId: name,
                            toMaxLinks: 99,
                            cursor: "pointer"
                        });

                    var lab = $(go.TextBlock, name,
                        {font: "7pt sans-serif"});

                    var panel = $(go.Panel, "Horizontal",
                        {margin: new go.Margin(2, 0)});

                    if (leftside) {
                        port.toSpot = go.Spot.Left;
                        port.toLinkable = true;
                        port.fromLinkable = true;
                        lab.margin = new go.Margin(1, 0, 0, 1);
                        panel.alignment = go.Spot.TopLeft;
                        panel.add(port);
                        panel.add(lab);
                    } else {
                        port.fromSpot = go.Spot.Right;
                        port.fromLinkable = true;
                        port.toLinkable = true;
                        lab.margin = new go.Margin(1, 1, 0, 0);
                        panel.alignment = go.Spot.TopRight;
                        panel.add(lab);
                        panel.add(port);
                    }
                    return panel;
                }

                <?php
                foreach ($objects as $object) {
                ?>
                makeTemplate("<?= $object->getTitle() ?>", "<?= $object->getFirstArgument() ?>", "<?= $object->getColor() ?>",
                    [
                        <?php
                        $points = '';
                        foreach ($object->inputLabels as $label) {
                            $points .= 'makePort("' . $label . '", true),';
                        }
                        $points = rtrim($points, ',');
                        ?>
                        <?= $points ?>
                    ],
                    [
                        <?php
                        $points = '';
                        foreach ($object->outputLabels as $label) {
                            $points .= 'makePort("' . $label . '", false),';
                        }
                        $points = rtrim($points, ',');
                        ?>
                        <?= $points ?>
                    ]);
                <?php
                }
                ?>

                myDiagram.linkTemplate =
                    $(go.Link,
                        {
                            routing: go.Link.AvoidsNodes, corner: 5,
                            relinkableFrom: true, relinkableTo: true
                        },
                        $(go.Shape, {stroke: "gray", strokeWidth: 2}),
                        $(go.Shape, {stroke: "gray", fill: "gray", toArrow: "Standard"})
                    );

                load();
                save();
            }

        </script>
    </head>
    <body onload="init()">
    <?php if (isset($headerText)) : ?>
        <?= $headerText ?>
    <?php endif; ?>
    <a href="https://github.com/kosuhin/avtomat"><img style="position: absolute; top: 0; left: 0; border: 0;" src="https://camo.githubusercontent.com/121cd7cbdc3e4855075ea8b558508b91ac463ac2/68747470733a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f6c6566745f677265656e5f3030373230302e706e67" alt="Fork me on GitHub" data-canonical-src="https://s3.amazonaws.com/github/ribbons/forkme_left_green_007200.png"></a>
    <div>
        <div id="common">
            <h1 style="text-align: center">{{ title }}</h1>
        </div>
        <div id="sample">
            <table width="100%">
                <tr>
                    <td width="25%" valign="top">
                        <?php if ($editEnable) : ?>
                        <div>
                            <h2>Название алгоритма</h2>
                            <form action="" method="get">
                                <div>
                                    <input type="text" name="algorithm_name" value="<?= $algoName ?>"/>
                                    <button>Загрузить</button>
                                </div>
                            </form>
                        </div>
                        <?php endif; ?>
                        <hr>
                        <h2>доступные блоки</h2>
                        <div class="blocks_arguments_wrapper">
                            <ul class="available_boxes">
                                <?php
                                $groupedObjects = [];
                                foreach ($objects as $object) {
                                    $groupedObjects[$object->group][] = $object;
                                }

                                foreach ($groupedObjects as $key => $group) {
                                    echo '<li class="block">'.$key.'</li>';
                                    foreach ($group as $object) {
                                        if ($object->isEditable) {
                                            echo '<li><span>' . $object->getTitle() . '</span><button onclick="add(\'' . $object->getTitle() . '\')">+</button></li>';
                                        }
                                    }
                                }
                                ?>
                            </ul>
                        </div>
                        <hr>
                        <div id="arguments">
                            <h2>Аргументы блоков</h2>
                            <!--                        {{ arguments|json }}-->
                            <div class="blocks_arguments_wrapper">
                                <table>
                                    <tr v-for="argument in diagram.model.nodeDataArray">
                                        <td>
                                            <strong>
                                                {{ argument.key }}
                                            </strong>
                                            <div v-for="(in_arg, index) in argument.arguments">
                                                <div>
                                                    <input class="index" type="hidden" v-model="index">
                                                    <input class="class_name" type="hidden" v-model="argument.key">
                                                    <input type="text" v-model="in_arg" @change="change">
                                                    <button @click="remove">-</button>
                                                </div>
                                            </div>
                                            <button @click="add(argument.key)">+</button>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </td>
                    <td valign="top">
                        <h2>Схема алгоритма</h2>
                        <div>
                            <div>
                                <span class="colorbox" style="background: #5166a0;"></span>
                                - Логический блок
                            </div>
                            <div>
                                <span class="colorbox" style="background: #8b0000;"></span>
                                - Логический блок изменяющий поток данных
                            </div>
                            <div>
                                <span class="colorbox" style="background: #dac062;"></span>
                                - Блок комментария
                            </div>
                            <br>
                        </div>
                        <div id="myDiagramDiv" style="border: solid 1px black; width: 100%; height: 670px"></div>
                    </td>
                </tr>
            </table>

            <div>
                <?php if ($editEnable) : ?>
                <div>
                    <button id="SaveButton" onclick="save()">Сохранить дамп</button>
                    <button onclick="load()">Загрузить</button>
                </div>
                <?php endif ?>

                <?php

                $algorithmJson = \Avtomat\Api\Avtomat::adaptAlgoToGoJS($algoName);
                //        echo $algorithmJson;

                ?>
                <table>
                    <tr>
                        <td width="50%" valign="top">
                            <form action="?action=save&algorithm_name=<?= $algoName ?>" method="post">
                                <?php if ($editEnable) : ?>
                                <button class="green">Сохранить алгоритм</button>
                                <?php endif; ?>
                                <div style="display: none;">
                                <h2>JSON дамп алгоритма</h2>
                                <textarea id="mySavedModel" name="algorithm_json" style="width:100%;height:700px"><?= $algorithmJson ?></textarea>
                                </div>
                            </form>
                        </td>
                        <td width="50%" valign="top">
                            <div style="margin-left: 20px; display: none;">
                                <form action="?action=run&algorithm_name=<?= $algoName ?>" method="post">
                                    <h2>Запуск и отладка алгоритма</h2>
                                    <button>Запуск</button>
                                    <h2>Входные данные (JSON)</h2>
                                    <textarea name="input_json" style="width: 100%; height: 100px"><?= $inputJson ?></textarea>
                                    <h2>Результат выполнения</h2>
                                    <div id="run_result">
                                        <?= nl2br($runResult) ?>
                                    </div>
                                </form>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <script>
            $(function () {
                $('.add_argument').on('click', function () {
                    var parent = $(this).parent().find('.block_arguments')
                    var prevHtml = parent.html();
                    parent.html(prevHtml + '<input type="text" value="" />');
                });

                $('.save_argument').on('click', function () {
                    alert('saving');
                });
            });
        </script>
    </div>
    <script src="<?= $assetsDir ?>assets/vue_core.js"></script>
    </body>
    </html>
    <?php
} catch (\Exception $e) {
    echo $e->getMessage();
}