<?php

namespace App\DataFixtures;

use App\Entity\Author;
use App\Entity\Book;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{

    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {

        // creation du user normal
        $user = new User();
        $user->setEmail('dev@mail.fr');
        $user->setRoles(['ROLE_USER']);
        $plainTextPassword = "azerty";
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $plainTextPassword
        );
        $user->setPassword($hashedPassword);
        $manager->persist($user);

        //creation de l'admin
        $admin = new User();
        $admin->setEmail('admin@mail.fr');
        $admin->setRoles(['ROLE_ADMIN']);
        $plainTextPassword = "azerty";
        $hashedPassword = $this->passwordHasher->hashPassword(
            $admin,
            $plainTextPassword
        );
        $admin->setPassword($hashedPassword);
        $manager->persist($admin);

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
            $book->setComment("Commentaire du livre et c'est le commentaire n°" . $i);
            $manager->persist($book);
        }

        $manager->flush();
    }
}
