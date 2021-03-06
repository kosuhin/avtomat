<?php

namespace Avtomat\Box;

use Avtomat\Contract\BoxContract;
use Avtomat\Contract\StorageContract;
use Avtomat\Contract\WriterContract;
use Avtomat\Controller\AlgorithmController;
use Avtomat\DependencyInjection\DI;
use Avtomat\Util\StrUtil;

/**
 * Базовый класс для всех черных ящиков
 */
class Box implements BoxContract
{
    public $group = 'None';

    public $color = '#5166a0';

    public $isEditable = true;

    public $inputLabels = ['input', 'comment'];

    public $outputLabels = ['output'];

    public $algorithm = 'none';

    /**
     * @var string
     */
    public $title = 'box';

    /**
     * @var int
     */
    protected $id;

    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * @var AlgorithmBox
     */
    private $baseAlogrithm;

    /**
     * Box constructor.
     * @param $id
     * @param $arguments
     */
    public function __construct($arguments=null)
    {
        $this->arguments = $arguments;
        StrUtil::debug('Аргументы ('.$this->getName().'): '.json_encode($arguments));
    }

    public function getTitle()
    {
        $path = explode('\\', static::class);
        $className = array_pop($path);
        return str_replace('Box', '', $className);
    }

    /**
     * @param $inputData
     */
    function run()
    {
        StrUtil::debug(sprintf('Run is not implemented in class %s! Check this class!', get_class($this)));
    }

    /**
     * @return mixed|string
     */
    public function nextArgument()
    {
        if (count($this->arguments) > 0) {
            return reset($this->arguments);
        }
        return 'none';
    }

    /**
     * @return array
     */
    public function allArguments()
    {
        return $this->arguments;
    }

    /**
     * @param AlgorithmBox $baseAlgorithm
     */
    public function setBaseAlgorithm(AlgorithmBox $baseAlgorithm)
    {
        $this->baseAlogrithm = $baseAlgorithm;
    }

    /**
     * @return AlgorithmBox
     */
    public function getBaseAlgorithm()
    {
        return $this->baseAlogrithm;
    }

    /**
     * @return int
     */
    function getId()
    {
        return $this->id;
    }

    /**
     * @param $id
     */
    function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @param $arguments
     */
    public function setArguments($arguments)
    {
        $this->arguments = $arguments;
    }

    /**
     * @return array|null
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @return mixed
     */
    public function getFirstArgument()
    {

        return $this->arguments ? array_shift($this->arguments) : '';
    }

    /**
     * @return string
     */
    public function getName()
    {
        $path = explode('\\', static::class);
        $className = array_pop($path);
        return str_replace('Box', '', $className).'::'.$this->getId();
    }

    /**
     * @param $serviceId
     * @return mixed|null
     */
    public function get($serviceId)
    {
        return DI::get($serviceId);
    }

    /**
     * @return AlgorithmController
     */
    public function getController()
    {
        return $this->get('controller');
    }

    /**
     * @return StorageContract
     */
    public function getResultsStorage()
    {
        return $this->get('results_storage');
    }

    /**
     * @return StorageContract
     */
    public function getInputsStorage()
    {
        return $this->get('inputs_storage');
    }

    /**
     * @return mixed|null
     */
    public function getFactory()
    {
        return $this->get('factory');
    }

    /**
     * @return string
     */
    public function getColor()
    {
        return $this instanceof WriterContract ? 'darkred' : $this->color;
    }

    /**
     * @return string
     */
    public function getAlgorithm()
    {
        return $this->algorithm;
    }

    /**
     * @param string $algorithm
     */
    public function setAlgorithm($algorithm)
    {
        $this->algorithm = $algorithm;
    }
}