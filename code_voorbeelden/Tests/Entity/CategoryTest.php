<?php

namespace TradusBundle\Tests\Entity;

use TradusBundle\Entity\Category;
use TradusBundle\Entity\Offer;
use TradusBundle\Tests\AbstractEntityTest;

/**
 * Class CategoryTest
 * @package TradusBundle\Tests\Entity
 */
class CategoryTest extends AbstractEntityTest
{
    const TEST_CLASS = 'TradusBundle\Entity\Category';

    /**
     * @inheritdoc
     */
    public function setUp() {
        parent::setUp();
    }

   public function testGetAllCategoryNames() {
        $category = $this->getMockCategory();
        $categories = $category->getAllCategoryNames(Offer::SUPPORTED_LOCALES);
        foreach(Offer::SUPPORTED_LOCALES as $locale) {
            foreach($categories[$locale] as $catKey => $catValue)
                $this->assertEquals('category'.($catKey+1).'-'.$locale, $catValue);
        }
   }

   public function testGetCatsArray() {
        $category = $this->getMockCategory();
        foreach(Offer::SUPPORTED_LOCALES as $locale) {
            $catsArray = $category->getCatsArray($locale);
            $catUrl = '/'.$locale.'/search/';
            $part = '';
            foreach($catsArray as $catKey => $catValue) {
                $catId = $catKey +1;
                if ($catId == 1) $part = '-c';
                if ($catId == 2) $part = '-t';
                if ($catId == 3) $part = '-s';
                $catUrl = $catUrl.'category'.$catId.'-'.$locale.$part.$catId.'/';

                $this->assertEquals($catId, $catValue['id']);
                $this->assertEquals('category'.$catId.'-'.$locale, $catValue['slug']);
                $this->assertEquals('category'.$catId.'-'.$locale, $catValue['label']);
                $this->assertEquals($catUrl, $catValue['url']);
            }
        }
   }

   public function testGetCats() {
       $category = $this->getMockCategory();
       foreach(Offer::SUPPORTED_LOCALES as $locale) {
           $cats = $category->getCats($locale);
           $expected = 'category1-'.$locale.'|category2-'.$locale.'|category3-'.$locale;
           $this->assertEquals($expected, $cats);
       }
   }

   public function testGetCatsIds() {
       $category = $this->getMockCategory();
       $catIds = $category->getCatsIds();

       foreach($catIds as $catKey => $catValue) {
           $catId = $catKey + 1;
           $this->assertEquals($catId, $catValue);
       }
   }

   public function testGetCatsNames() {
       $category = $this->getMockCategory();
       foreach(Offer::SUPPORTED_LOCALES as $locale) {
           $catNames = $category->getCatsNames($locale);
           foreach($catNames as $catKey => $catValue) {
               $catId = $catKey + 1;
               $this->assertEquals('category' . $catId . '-' . $locale, $catValue);
           }
       }
   }

   public function testGetChildrenCats() {
       $category = $this->getMockCategory();
       foreach(Offer::SUPPORTED_LOCALES as $locale) {
           $childCats = $category->getChildrenCats($locale);
           $this->assertEquals(false, $childCats);
       }

       $parentCategory = $category->getParent();
       foreach(Offer::SUPPORTED_LOCALES as $locale) {
           $childCats = $parentCategory->getChildrenCats($locale);
           $this->assertEquals($childCats[0]['slug'], 'category3-'.$locale);
           $this->assertEquals($childCats[0]['name'], 'category3-'.$locale);
       }

       $parentParentCategory = $parentCategory->getParent();
       foreach(Offer::SUPPORTED_LOCALES as $locale) {
           $childCats = $parentParentCategory->getChildrenCats($locale);
           $this->assertEquals($childCats[0]['slug'], 'category2-'.$locale);
           $this->assertEquals($childCats[0]['name'], 'category2-'.$locale);
           $this->assertEquals($childCats[1]['slug'], 'category3-'.$locale);
           $this->assertEquals($childCats[1]['name'], 'category3-'.$locale);
       }
   }

   public function testGetSlugUrl() {
       $category = $this->getMockCategory();
       foreach(Offer::SUPPORTED_LOCALES as $locale) {
           $slugUrl = $category->getSlugUrl($locale);
           $this->assertEquals('category1-'.$locale.'/category2-'.$locale.'/category3-'.$locale, $slugUrl);
       }
   }

   public function testGetSearchSlugUrl() {
       $category = $this->getMockCategory();
       foreach(Offer::SUPPORTED_LOCALES as $locale) {
           $searchUrl = $category->getSearchSlugUrl($locale);
           $this->assertEquals('category1-'.$locale.'-c1/category2-'.$locale.'-t2/category3-'.$locale.'-s3/', $searchUrl);
       }
   }
}