<?php

/**
 * Created by PhpStorm.
 * User: Serhii
 * Date: 08.05.2018
 * Time: 16:43
 */
class Algorithm
{
    /**
     * Входной набор тестовых данных
     * @var array
     */
    private $testData = [];

    /**
     * дерево
     * @var Tree
     */
    private $tree;
    /**
     *  Категории результирующих классов
     * @var array
     */
    private $classes = [];


    //контактные линзы
    /**
     * Кол-во элементов результирующего класса/атрибута перед разбиением
     * @var array
     */
    private $dataCount = [];

    /**
     * Таблица вероятностей появления каждого элемента в результирующем атрибуте
     * @var array
     */
    private $dataProbability = [];


    //словарь вероятностей
    private $dictionaryProbability = [];

    /**
     * Категории атрибутов с их значениями
     * @var array
     */
    private $categories = [];

    /**
     * энтропия перед разбиением
     * @var
     */
    private $entropyBeforeSplitting;
    /**
     * Энтропия после разбиение
     * @var array
     */
    private $entropyAfterSplitting = [];

    function __construct(array $testData)
    {
        $this->testData = $testData;
    }

    /**
     * Нахождение энтропии перед рабиением
     * @param array $data
     */
    private function findEntropyBeforeSplitting(array $data):void
    {

        // Общее кол-во элементов результирующего атрибута
        $countElem = 0;
        //Рассчитываем кол-во элементов, относщихся к определенным знаячениям результирующего класса/атрибута
        foreach ($data as $item) {
            //Получаем значение результирующего атрибута для каждого item набора данных
            $lastElement = end($item);
            if (!array_key_exists($lastElement, $this->dataCount)) {
                $this->dataCount[$lastElement] = 0;
            }
            $this->dataCount[$lastElement]++;
            //Подсчитываем общее кол-во элементов результриющего атрибута
            $countElem++;
        }
        //Находим вероятности появления каждого значения в результирующем классе
        foreach ($this->dataCount as $key => $value) {
            $this->dataProbability[$key] = $value / $countElem;
        }
        //Рассчитываем энтропию для данного атрибут
        $entropy = 0;
        foreach ($this->dataProbability as $value) {
            $entropy += (-$value * log($value, 2));
        }
        $this->entropyBeforeSplitting = $entropy;
        return;
    }

    /**
     * Построение словаря вероятностей всех атрибутов
     * @param array $data
     */
    private function buildDictionary(array $data):void
    {
        //Массив кол-ва появления всех значений атрибутов
        $dictionaryCount = [];
        //Массив категорий результирующего класса
        $classes = array_keys($this->dataProbability);
        //Проинициализируем их начальными значениями
        foreach ($classes as $class) {
            $this->classes[$class] = 0;
        }
        //Посчитаем кол-во появления всех элементов атрибутов
        for ($i = 0; $i < count($data); $i++) {
            //Результирующий тип значения набора
            $type = end($data[$i]);
            //Делаем проход по всем атрибутам данного значения
            foreach ($data[$i] as $attribute => $value) {
                if ($value != $type) {
                    //Объявляем начальные значения
                    if (!array_key_exists($value, $dictionaryCount)) {
                        $dictionaryCount[$value] = $this->classes;
                        $dictionaryCount[$value]['instances'] = [];
                    }
                    $dictionaryCount[$value][$type]++;
                    //Список экземпляров, принадлежащих данному значению атрибута
                    array_push($dictionaryCount[$value]['instances'], $i);
                }
            }
        }
        //Рассчитываем вероятности
        foreach ($dictionaryCount as $attribute => $item) {
            $this->dictionaryProbability[$attribute] = [];
            foreach ($item as $type => $value) {
                if ($type == 'instances') {
                    $this->dictionaryProbability[$attribute][$type] = $value;
                    continue;
                }
                $this->dictionaryProbability[$attribute][$type] = $value / count($item['instances']);
            }
        }
        return;
    }


    /**
     * Определение всех атрибутов с их значениями
     * @param array $data
     */
    private function findCategories(array $data):void
    {
        foreach ($data as $item) {
            $class = end($item);
            //Определеяем значение результирующего атрибута
            $classType = key($item);
            //Перебираем все остльные атрибуты
            foreach ($item as $attribute => $value) {
                if ($attribute != $classType) {
                    //Инициализируем массив категорий
                    if (!array_key_exists($attribute, $this->categories)) {
                        $this->categories[$attribute] = [];
                    }
                    //Если значение не было еще добавлено, то добавляем
                    if (!in_array($value, $this->categories[$attribute])) {
                        array_push($this->categories[$attribute], $value);
                    }
                }
            }
        }
        return;
    }


    /**
     * Нахождение энтропии после разбиения для каждого атрибута
     * @param $category
     * @param $value
     */
    private function findEntropyAfterSplitting($category, $value):void
    {
        $tableProbability = [];
        $sum = 0;
        //Формируем новую таблицу вероятностей после разбиения
        foreach ($this->dictionaryProbability as $attribute => $values) {
            if (in_array($attribute, $value)) {
                $tableProbability[$attribute] = $values;
                $tableProbability[$attribute]['amount'] = count($values['instances']);
                $sum += $tableProbability[$attribute]['amount'];
            }
        }

        //находим энтропию
        $entropy = 0;
        foreach ($tableProbability as $key => $values) {
            $temp = 0;
            foreach ($values as $attribute => $value) {
                //Рассчитаем по всем атрибутам, кроме instances and amount
                if (!in_array($attribute, ['instances', 'amount']) && $value != 0) {
                    $temp += (-$value * log($value, 2));
                }
            }
            $entropy += $values['amount'] / $sum * $temp;
        }
        //Массив энтропии после разбиения для каждой категории
        $this->entropyAfterSplitting[$category] = $entropy;
        return;
    }

    /**
     * Нахождение максимального прироста информации
     * @return array
     */
    private function findMaxGain():array
    {
        //Рассчитаем энтропию после разбиения для каждого атрибута
        foreach ($this->categories as $category => $value) {
            $this->findEntropyAfterSplitting($category, $value);
        }

        //Рассчитаем прирост информации
        $gain = [];
        foreach ($this->entropyAfterSplitting as $category => $value) {
            $gain[$category] = $this->entropyBeforeSplitting - $value;
        }

        //Нахождение максимального прироста информации
        $max = PHP_INT_MIN;
        $key = '';
        foreach ($gain as $category => $value) {
            if ($value > $max) {
                $max = $value;
                $key = $category;
            }
        }
        return [$key, $max];
    }

    /**
     * Разбиение по атрибуту(тип ветки => [значения результируюшего класса, входящие в данный тип ветки])
     * @param array $data
     * @param string $category
     * @return array
     */
    private function explodeByAttribute(array $data, string $category):array
    {
        //Получаем все значения данной категории
        $categoryValues = $this->categories[$category];
        $table = [];
        foreach ($categoryValues as $value) {
            $table[$value] = [];
        }
        foreach ($data as $item) {
            foreach ($item as $attribute => $value) {
                if ($attribute == $category) {
                    array_push($table[$value], end($item));
                }
            }
        }
//        var_dump($table);
        return $table;
    }

    /**
     * Построение дерева
     * @param Tree $tree
     */
    public function makeTree(Tree $tree):void
    {
        $this->tree = $tree;
//        var_dump($this->testData);
        //Строим дрерво рекурсивно
        $this->recursiveBuildTree($this->testData, null, null);
        return;
    }


    private function recursiveBuildTree(array $data, ?Node $child, ?string $categoryType):void
    {
        //Обнуление свойств
        $this->resetProperties();

        //Нахождение энтропии перед рабиением
        $this->findEntropyBeforeSplitting($data);
        //Построение словаря вероятностей атрибутов
        $this->buildDictionary($data);
        //Определяем возможные значения категорий
        $this->findCategories($data);

        //Определяем максимальный прирост информации
        list($category, $value) = $this->findMaxGain();

        echo 'категория с максимальным приростом: ' . $category . ' = ' . $value . '<br>';

        //Если прирост информации больше 0, то создаем узел с данным атрибутом
        if ($value != 0) {
            $node = new Node($category);
            //Получаем все возможные значения данного атрибута
            $valuesCategory = $this->categories[$category];
            $children = [];
            foreach ($valuesCategory as $item) {
                $children[$item] = null;
            }
            $node->setChildren($children);

            //Если корень дерева не задан, задаем
            if ($this->tree->getRoot() == null) {
                $this->tree->setRoot($node);
            } else {
                //Добавляе узел
                $child->addNode($categoryType, $node);

            }
        }

        //если прирост информации равен 0, то дальнейшее разбиение не требуется
        if ($value == 0) {
            echo "добавили в дерево с веткой $categoryType значение " . end($data[0]);
            //Добавляем в дерево веткой $categoryType значение резуьтирующего класса
            $child->addChild($categoryType, end($data[0]));

            var_dump($data);
            echo '<pre>';
            print_r($this->tree);
            echo '<pre>';
        } else {
            //разбиваем по атрибуту, имеющему наибольший прирост информации
            $explodedData = $this->explodeByAttribute($data, $category);
            foreach ($explodedData as $type => $item) {
                //Если все элементы набора принадлежат одному типу результирующего класса
                if (count($item) == 1) {
                    $val = array_shift($item);
                    //добавили в дерево с веткой $type значение $val
                    $node->addChild($type, $val);
                    echo "добавили в дерево с веткой $type значение $val ";
                    echo '<pre>';
                    print_r($this->tree);
                    echo '<pre>';
                } else {
                    /*Если элементы в наборе принадлежат разным типам, то нужно произвести дальнейшее разбиение
                        Удалим из набора данных наборы, принадлежащие другим веткам и данный результируюший атрибут
                    (атрибут, по которому происходи разбиение)
                    */
                    $d = $data;
                    $count = count($d);
                    for ($i = 0; $i < $count; $i++) {
                        //Удаляем наборы, принадлежащие другим веткам
                        if ($d[$i][$category] != $type) {
                            unset($d[$i]);
                        } else {
                            //Удаляем текущий атрибут, по которому происходит разбиение
                            unset($d[$i][$category]);
                        }
                    }
                    //Упорядочиваем по индексам
                    $d = array_values($d);
                    echo 'Отправляем на след итерацию.<br>';
                    var_dump($d);
                    echo '<pre>';
                    print_r($this->tree);
                    echo '<pre>';
                    echo '<hr>';
                    //Рекурсивное разбиение
                    $this->recursiveBuildTree($d, $node, $type);
                }
            }
        }
        return;
    }

    /**
     * Сброс свойств на начальное значение
     */
    private function resetProperties():void
    {
        //контактные линзы
        $this->dataCount = [];
        $this->dataProbability = [];

        //словарь вероятностей
        $this->dictionaryProbability = [];

        //категории атрибутов
        $this->categories = [];

        $this->classes = [];
        //энтропия перед разбиением
        $this->entropyBeforeSplitting = 0;
        //Энтропия после разбиение
        $this->entropyAfterSplitting = [];
        return;
    }

}