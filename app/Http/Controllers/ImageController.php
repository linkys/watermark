<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImageRequest;
use ColorThief\ColorThief;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class ImageController extends Controller
{
    /**
     * @param ImageRequest $request
     * @return RedirectResponse
     */
    public function upload(ImageRequest $request)
    {
        $image_path = $request->file('image')->store('images', 'public');
        $image_url = $this->getWatermarkedImage($image_path);

        return redirect()->back()->with(compact('image_url'));
    }

    /**
     * @param string $image_path
     * @return string
     */
    private function getWatermarkedImage(string $image_path): string
    {
        $rgb = ColorThief::getColor(asset($image_path));

        $watermark = resource_path('images/red-watermark.jpg'); // default watermark

        if (($rgb[1] + $rgb[2]) < $rgb[0]) { // red
            $watermark = resource_path('images/black-watermark.jpg');
        } elseif (($rgb[0] + $rgb[2]) < $rgb[1]) { // green
            $watermark = resource_path('images/red-watermark.jpg');
        } elseif (($rgb[0] + $rgb[1]) < $rgb[2]) { // blue
            $watermark = resource_path('images/yellow-watermark.jpg');
        }

        $img = Image::make(asset($image_path));
        $img_width = $img->getWidth();

        $watermark = Image::make($watermark);
        $watermark->resize(intval($img_width / 5), null, function ($constraint) {
            $constraint->aspectRatio();
        });

        $img->insert($watermark, 'bottom-right', intval($img_width / 20), intval($img_width / 20));
        $img->encode('jpg', 80);

        $watermarked_img_path = 'images_watermarked/' . md5(time() . Str::random()) . '.jpg';

        Storage::disk('public')->put($watermarked_img_path, $img);

        return Storage::url($watermarked_img_path);
    }
}
