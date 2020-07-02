<?php

namespace App\DataFixtures;

use App\Entity\Book;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $faker = \Faker\Factory::create();

        for($i=0; $i < 1000; $i++) {
            $book = new Book();
            $book->setTitle($faker->sentence());
            $book->setPagesCount($faker->numberBetween(30, 3000));
            $book->setCover($faker->imageUrl);
            $book->setCreatedDate($faker->dateTimeBetween("- 6 months", "now"));

            $manager->persist($book);
        }

        $manager->flush();
    }
}
