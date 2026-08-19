<?php
namespace App\Services;

use App\Models\MediaFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MediaManager
{
    public const MIMES = ['image/jpeg','image/png','image/gif','image/webp','application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','text/plain'];
    public function store(UploadedFile $file, User $user, array $attributes=[]): MediaFile
    {
        $mime=(string)$file->getMimeType();
        if(!in_array($mime,self::MIMES,true)) throw ValidationException::withMessages(['file'=>__('This file type is not permitted.')]);
        $extension=$this->extension($mime);
        $directory=now()->format('Y/m');$name=Str::uuid().'.'.$extension;$path=$file->storeAs($directory,$name,'uploads');
        if(!$path) throw ValidationException::withMessages(['file'=>__('The uploaded file could not be stored.')]);
        try {
            return MediaFile::create(array_merge([
                'disk'=>'uploads','path'=>$path,'original_name'=>Str::limit($file->getClientOriginalName(),255,''),'mime_type'=>$mime,
                'size'=>$file->getSize(),'checksum'=>hash_file('sha256',$file->getRealPath()),'uploaded_by'=>$user->id,
            ],$attributes));
        } catch (\Throwable $exception) {
            Storage::disk('uploads')->delete($path);
            throw $exception;
        }
    }
    public function delete(MediaFile $media): void
    {
        if($media->path && $media->disk==='uploads') Storage::disk('uploads')->delete($media->path);
        $media->delete();
    }
    private function extension(string $mime): string
    {
        return match($mime){'image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp','application/pdf'=>'pdf','application/msword'=>'doc','application/vnd.openxmlformats-officedocument.wordprocessingml.document'=>'docx','application/vnd.ms-excel'=>'xls','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'=>'xlsx',default=>'txt'};
    }
}
