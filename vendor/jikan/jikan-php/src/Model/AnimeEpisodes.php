<?php declare(strict_types=1);

namespace Jikan\JikanPHP\Model;

class AnimeEpisodes
{
    /**
     * @var AnimeEpisodesdataItem[]
     */
    protected $data = [];

    /**
     * @var PaginationPagination
     */
    protected $pagination;

    /**
     * @return AnimeEpisodesdataItem[]
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param AnimeEpisodesdataItem[] $data
     */
    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getPagination(): PaginationPagination
    {
        return $this->pagination;
    }

    public function setPagination(PaginationPagination $paginationPagination): self
    {
        $this->pagination = $paginationPagination;

        return $this;
    }
}
