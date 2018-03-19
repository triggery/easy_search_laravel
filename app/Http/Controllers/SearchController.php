<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Models\Category;
use App\Models\Offer;

class SearchController extends Controller
{
    private $allItems = [];
    private $additionalWords = [];
    private $findCollection;

    private $myActiveLanguages = []; //["ru", "ro"];
    private $myDictionary = [];

    private $moreImportant;

    public function __construct() {

        $this->findCollection = Category::all();
        $this->allItems =  $this->findCollection->toArray();

        $this->myActiveLanguages = ["ru", "ro"];
        $this->initDictionary();

        $this->moreImportant = 0;

//        dd($this->myDictionary);
    }

    public function searchApi(Request $request) {
        //echo "search string: $request->string", "<br>";
        if(!isset($request->string))
            return redirect()->route('root');

        $findCategories = [];
        $filters = [];
        $breadCrumb = [];
        $returnOffers = [];
        $searchArray = $this->PrapareSearchString($request->string);     // удаляем служебные символы и повтор. пробелы
        $countWord = count($searchArray);
        if($countWord == 0)
            return;

        $findCategories = $this->returnFoundWords($searchArray);
        $countFindCategories = count($findCategories);

        //dd($findCategories, $countFindCategories);
        if($countFindCategories == 0) {
            return view('search-page',  [
                'searchString' => $request->string,
                'breadCrumb' => $breadCrumb,
                'filters' => $filters,
                'offers' => $returnOffers,
            ]);
        }

        if($countFindCategories == 1) {
            $categoriesIds = $findCategories[0];
            if( $this->isMainCategory($categoriesIds[0]) ) {                          // cars, food
                $filters = $this->getChildsFilters($categoriesIds);
                //dd($findCategories, "Main categories: ", $filters);
            }
            else if( $this->isFinalCategory($categoriesIds[0]) && count($categoriesIds) > 1 ) {  //  Samsung, Asus, Acer
                // Filters: up - parents
                $filters = $this->getParentsFilters($categoriesIds);
                //dd($findCategories, "categories: ", count($categoriesIds), "Parents filters", $filters);
            }
            else if( !$this->isFinalCategory($categoriesIds[0]) && count($categoriesIds) == 1 ) {    // Audi, cars, 
                // Filters: down - childrens
                // Здесь должны заполняться обьявления по данным фильтрам категорий
                $filters = $this->getChildsFilters($categoriesIds);
                //dd($findCategories, "categories: ",count($categoriesIds), "Inter_mediate", $filters);
                $categoriesIds = $filters;
            }
            else if( $this->isFinalCategory($categoriesIds[0]) && count($categoriesIds) == 1 ) { // A8 
                // Link
                $breadCrumb = $this->getBreadCrumb($categoriesIds);
                //dd($findCategories, "categories: ", count($categoriesIds), "Final", $breadCrumb);
            }
        }
        else if( $countFindCategories == 2 ) {

            $result = $this->isOneCategoryWordCollocation($searchArray);
            if(!empty($result)) {           // Сначало нужно попробовать найти по сочетанию из 2-х слов в словаре и в БД
                $categoriesIds = array_shift($result);
                $breadCrumb = $this->getBreadCrumb($categoriesIds);
            }
            else {
                $exact = $this->exOccurrence($findCategories[0], $findCategories[1]);
                if( !empty($exact) ) {

                    $findCats = $findCategories;
                    $word1 = array_shift($findCats[0]);
                    $word2 = array_shift($findCats[1]);

                    //dd($exact);

                    if( !$this->isFinalCategory($word1) && !$this->isFinalCategory($word2) ) {  // "transport cars"
                        $filters = $this->getChildsFilters($findCategories[$this->moreImportant]);
                        $categoriesIds = $filters;
                        //dd("transport cars", $categoriesIds);
                    }
                    else if ( !$this->isFinalCategory($exact[0]) ) {  // "bmw cars"
                        $categoriesIds = $findCategories[$this->moreImportant];
                        $filters = $this->getChildsFilters($categoriesIds);
                        //dd("bmw cars", $categoriesIds, $filters);
                    }
                    else if ( $this->isFinalCategory($exact[0]) ) {  // "bmw x5"
                        $categoriesIds = $exact;
                        $breadCrumb = $this->getBreadCrumb($categoriesIds);
                    }
                }
                else {
                    if( $this->isFinalCategory($findCategories[0][0]) ) {   // Если первое слово - это финальная категория
                        $categoriesIds = $findCategories[0];
                    }
                    else {
                        $categoriesIds = $findCategories[1];
                    }
                    $breadCrumb = $this->getBreadCrumb($categoriesIds);
                }
            }
        }
        else if($countFindCategories > 2) {
            $ids = $this->walkInPairs($searchArray);
            $categoriesIds = $ids[0];
            $breadCrumb = $this->getBreadCrumb($categoriesIds);
            //dd($categoriesIds, $breadCrumb);
        }

        $returnOffers = $this->getOffersWithImagesByID($categoriesIds);
        //dd($returnOffers, "filters: ", $filters, "breadCrumb", $breadCrumb);
        return view('search-page',  [
            'searchString' => $request->string,
            'breadCrumb' => $breadCrumb,
            'filters' => $filters,
            'offers' => $returnOffers,
        ]);
    }

    // Найти в парах слов конечную категорию
    // search string: audi+a6+allroad+green
    // 0: audi a6
    // 1: a6 allroad - конечная, "id" => 1341
    private function walkInPairs($searchArray) {
        $cnt = count($searchArray);
        $i = 0;
        while ($i < $cnt-1) {
            //echo $i, ": ".$searchArray[$i]." ".$searchArray[$i+1] . '<br>';
            $testString = [ $searchArray[$i]." ".$searchArray[$i+1] ];
            $ids = $this->returnFoundWords($testString);
            if(!empty($ids))
                return $ids;
            $i++;
        }
    }

    public function getOffersWithImagesByID($categoriesIds) {
        $returnOffers = [];
        foreach ($categoriesIds as $oneCat) {
            $offers = Offer::where('category_id', $oneCat['id'])->limit(48)->get()->toArray();
            if(!empty($offers)) {
                $offersWithImg = $this->getOffersWithImages($offers);
                $returnOffers = array_merge($returnOffers, $offersWithImg);
            }
        }
        return $returnOffers;
    }

    private function getOffersWithImages( $offers ) {
        $retOffers = [];
        foreach ($offers as $oneOffer) {
            $one = str_replace('media', 'storage/images', json_decode($oneOffer['images']));

            $retOffers[] = array('id' => $oneOffer['id'], 
                                'text' => $oneOffer['text'], 
                                'images' => array_shift($one), 
                                'price' => $oneOffer['price'],
                            );
        }
        return $retOffers;
    }

    public function getNameCategoryWithId($categoryId) {
        $res = $this->findCollection->where('id', $categoryId)->first();
        return $res;
    }

    //Если найдена одна категория, возвращаем навигационная цепочку
    public function getBreadCrumb($categories) {
        $breadCrumb = [];                                      // array('id' => $id, 'name' => $name);
        $category = $categories; // $categories[2];
            if(is_array($category))
                $category = array_shift($category);
            $id = $category['id'];
            while( $id != null ){
                $res = $this->findCollection->where('id', $id)->first();
                $id = $res['parent_id'];
                $breadCrumb [] = array('id' => $res['id'], 'name' => $res['name']/*, 'parent_id' => $res['parent_id']*/);
            }
            return(array_reverse($breadCrumb));
    }

    private function getParentsFilters($categories) {
        $variousCats = [];
        foreach ($categories as $oneCat) {
            $res = $this->findCollection->where('id', $oneCat['parent_id'])->first();
            if($res)
                $variousCats [] = array('id' => $oneCat['id'], 'name' => $res['name']);
            /*else {
                $item = $this->findCollection->where('id', $oneCat['id'])->first();
                $variousCats [] = array('id' => $oneCat['id'], 'name' => $item['name']);
            }*/
        }
        return $variousCats;
    }

    private function getChildsInCategory($category) {
        $res = $this->findCollection->where('id', $category['id'])->toArray();
        $childsArr = array_shift($res);
        return json_decode($childsArr['children']);
    }

    public function getChildsFilters($categories) {
        $variousCats = [];
        foreach ($categories as $oneCat) {
            $childs = $this->getChildsInCategory($oneCat);
            foreach ($childs as $child) {
                $res = $this->findCollection->where('id', $child)->first();
                $variousCats [] = [
                     'id' => $child,
                     'name' => $res['name'],
                     'parent_id' => $res['parent_id'],
                     'position' => $res['position'],
                ];
            }
        }
        return $variousCats;
    }

    private function searchFunc($word) {
        $mainCategory = $this->SearchFromAllCategoriesWord($word);      // ищем во всех категориях
        if(empty($mainCategory))                                        // ищем в словаре и присваиваем в $mainCategory
        {
            $mainCategory = $this->SearchFromAllDictinary($word);
        }                
        return $mainCategory;
    }

    private function returnFoundWords($arrayWords) {
        $words = [];
        foreach ($arrayWords as $oneWord) {
            $var = $this->searchFunc($oneWord);
            if(!empty($var)) {
                $words[] = $var;
            }
            else
                $this->additionalWords[] = $oneWord;
        }
        return $words;
    }

    private function exOccurrence($a, $b = null) {
        if(!isset($b))
            $b = $a;

        $var = $this->isOccurrence($a, $b);
        if( empty($var) ) {
            $var = $this->isOccurrence($b, $a);
            $this->moreImportant = 0;   // Позиция самой глубокой категории: если $a, то 0;
        }
        else
            $this->moreImportant = 1;   // Позиция самой глубокой категории: если $b, то 1;

        if( empty($var) ) {
            //echo "not is occurrences... <br>";  // а они в разных категориях
            return [];
        }
        return $var;
    }

    private function isOccurrence($a, $b) {
        $find = [];
        foreach ($a as $a_el) {
            foreach ($b as $b_el) {
                if( $a_el['id'] == $b_el['parent_id'] ) {
                    $find[] = array('id' => $b_el['id'], 'parent_id' => $a_el['id']);
                    return $find;
                }
            }
        }
        return $find; // OR [] ?
    }

    private function SearchFromAllCategoriesWord($myString) {
        $mainCat = [];
        foreach ($this->allItems as $headItem) {
            if( preg_match("/\b".$myString."[\W]?\b/ui", $headItem['name']) ) {
                $mainCat[] = array('id' => $headItem['id'], 'parent_id' => $headItem['parent_id']);
            }
        }
        return $mainCat;
    }

    private function PrapareSearchString($string) : array {
        $result_array = [];
        $punctuation = ['.', ',', ':', ';', '!', '?', '*', '&', '@'];
        $result_string = stripslashes(mb_strtolower(trim($string)));
        $result_string = str_replace($punctuation, ' ', trim($result_string));
        $result_string = preg_replace("/ {2,}/", ' ', $result_string);
        $result_array = explode(" ", $result_string);
        return $result_array;
    }

    //промежуточная категория или конечная
    public function isFinalCategory($categoryId) {
        $res = $this->findCollection->where('id', $categoryId['id'])->first();
        return (!$res['childless']) ? false : true;
    }

    private function isMainCategory($categoryId) {
        return ($categoryId['parent_id'] == null) ? true : false;
    }

    private function initDictionary() {
        $allCategoriesWords = $this->findCollection->all();
        foreach ($allCategoriesWords as $oneCategoryWords) {
          $words = json_decode($oneCategoryWords['words'], true);
          foreach ($this->myActiveLanguages as $activeLanguage) {
            if ($words[$activeLanguage]) {
                $this->myDictionary[$oneCategoryWords['id']][$activeLanguage] = $words[$activeLanguage];
            }
          }
        }
    }

    private function SearchFromAllDictinary($myString) {
        $subCategories = [];
        foreach ($this->myDictionary as $key => $element) {
            foreach ($this->myActiveLanguages as $activLang) {
                if( !isset($element[$activLang] )) continue;
                foreach ($element[$activLang] as $subelement) {
                    if( preg_match("/\b".$myString."[\W]?\b/ui", $subelement) ) {
                        $parent_id = $this->findCollection->where('id', $key)->first();
                        $subCategories[] = ['id' => $key, 'parent_id' => $parent_id['parent_id']];
                    }
                }
            }
        }
        return $subCategories;
    } 

    private function isOneCategoryWordCollocation($searchArray) {
        $testString = [ $searchArray[0]." ".$searchArray[1] ];
        return $this->returnFoundWords($testString);
    }
}
