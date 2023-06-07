<?php

namespace App\Controller;

use App\Entity\Author;
use App\Entity\Book;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class AuthorController extends AbstractController
{
    #[Route('/api/authors', name: 'app_author', methods: ['GET'])]
    public function getAllAuthor(
        AuthorRepository $authorRepository, 
        SerializerInterface $serializer,
        ): JsonResponse
    {
        
        $authorList = $authorRepository->findAll();
        $jsonAuthorList = $serializer->serialize($authorList, 'json', ['groups' => 'getAuthors']);

        return new JsonResponse(
            $jsonAuthorList,
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/api/authors/{id}', name: 'app_author_id', methods: ['GET'])]
    public function getOneAuthor(
        Author $author, 
        SerializerInterface $serializer,
        ): JsonResponse
    {
        
        $jsonAuthorList = $serializer->serialize($author, 'json', ['groups' => 'getAuthors']);

        return new JsonResponse(
            $jsonAuthorList,
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/api/authors/{id}', name: 'deleteAuthor', methods: ['DELETE'])]
    public function deleteAuthor(Author $author, EntityManagerInterface $em): JsonResponse {
        
        $em->remove($author);
        
        $em->flush();
        dd($author->getBooks());
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
