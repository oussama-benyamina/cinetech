<?php declare(strict_types=1);

namespace Jikan\JikanPHP\Model;

class CharacterImagesWebp
{
    /**
     * Image URL WEBP.
     *
     * @var string|null
     */
    protected $imageUrl;

    /**
     * Small Image URL WEBP.
     *
     * @var string|null
     */
    protected $smallImageUrl;

    /**
     * Image URL WEBP.
     */
    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    /**
     * Image URL WEBP.
     */
    public function setImageUrl(?string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    /**
     * Small Image URL WEBP.
     */
    public function getSmallImageUrl(): ?string
    {
        return $this->smallImageUrl;
    }

    /**
     * Small Image URL WEBP.
     */
    public function setSmallImageUrl(?string $smallImageUrl): self
    {
        $this->smallImageUrl = $smallImageUrl;

        return $this;
    }
}
