<?php

namespace App\Controller;

use App\Entity\Author;
use App\Repository\AuthorRepository;
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

    #[Route('/api/authors/{id}', name: 'app_author', methods: ['GET'])]
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

    
}
