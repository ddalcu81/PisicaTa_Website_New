<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    @php($root = rtrim(config('app.url'), '/'))
    <url><loc>{{ $root }}/</loc></url>
    <url><loc>{{ $root }}/articole.php</loc></url>
    @foreach ($categories as $category)
        <url><loc>{{ $root.$category->publicUrl() }}</loc></url>
    @endforeach
    @foreach ($articles as $article)
        <url><loc>{{ $root.$article->publicUrl() }}</loc><lastmod>{{ $article->updated_at->toDateString() }}</lastmod></url>
    @endforeach
</urlset>
