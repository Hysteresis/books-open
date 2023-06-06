<?php

namespace App\DataFixtures;

use App\Entity\Author;
use App\Entity\Book;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Faker\Factory::create('fr_FR');
        $listAuthor = [];

        //? Creation des Authors
        for($i = 0; $i <=10; $i++){
            $author = new Author;
            $author->setLastname($faker->lastName());
            $author->setFirstname($faker->firstName());
            $manager->persist($author);
            $listAuthor[] = $author;
        }

        //? Creation des books
        for($i = 0; $i <= 20; $i++){
            $book = new Book;
            $book->setTitle($faker->catchPhrase());
            $book->setCoverText("La couverture du livre numero : " .$i);
            $book->setAuthor($listAuthor[array_rand($listAuthor)]);
            $manager->persist($book);
        }

        $manager->flush();
    }
}
