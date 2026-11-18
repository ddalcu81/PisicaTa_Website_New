# Editorial photography

Each imported article has a photo under `public/media/articles/`. The manifest at
`resources/editorial/article-covers.json` matches it to the original article ID
and records the creator, source page, license, dimensions, and Romanian alt text.
Article pages link to the original and display the license. The copies were
resized, re-encoded as WebP, and may be cropped in the page layout. Each photo
retains the license assigned by its source. Source licenses apply to the individual
images in this directory. Covers uploaded by an editor take priority.

## Generic photography

The covers in `public/media/editorial/` were created with the built-in ImageGen tool. The hero has 640 and 1280 pixel WebP versions. The contact sheet was split into four 740 pixel WebP covers.

## Hero prompt

Use case photorealistic-natural. A single broad horizontal editorial hero photograph for Romanian cat magazine Pisica Ta. Beautiful orange tabby adult cat reclining on a sand colored textured linen sofa, turned toward the camera, large expressive amber eyes, soft morning sunlight, tasteful earthy beige, cream and brown, shot on a real full frame camera, textured detail, cozy elegant home atmosphere, entire face in middle-right portion, some breathing room, no typography, no graphics or watermark. Landscape 3:2 crop. Warm, beautiful and quietly curious.

## Cover prompt

Use case photorealistic-natural. Contact sheet of FOUR independently framed landscape cat photographs in exact 2x2 grid with absolutely no gaps or borders, to be cropped into four files. Top left: silver gray British Shorthair curious green eyes on a cream armchair. Top right: small orange kitten nestling in a light soft blanket. Bottom left: beautiful dark tortoiseshell cat with very clear amber eyes resting on tan upholstery. Bottom right: white and apricot cat sitting on a sunlit windowsill with olive green foliage softly blurred in background. Consistent high-end natural editorial lifestyle photography, warm natural window light, subtle earthy cream palette, each subject wholly inside its quadrant, no text, no watermark. Landscape sheet.
