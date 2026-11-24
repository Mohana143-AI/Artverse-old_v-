<?php
/**
 * ArtSphere Seed Script
 * Populates each of the 10 categories with 30 unique, rich listings.
 * 10 categories * 30 listings = 300 total listings.
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/image_pools.php';

// Titles and descriptions tailored per category
$category_items = [
    'interior-design' => [
        ['Modern Minimalist Living Room Styling', 'Complete Scandinavian-inspired living room design with moodboards and material specifications.', 350.00, 'service'],
        ['Luxury Penthouse Master Suite Concept', 'High-end contemporary bedroom interior design with customized illumination plan.', 480.00, 'service'],
        ['Warm Japandi Style Dining Room Plan', 'Harmonious blend of Japanese minimalism and warm Scandinavian textures.', 320.00, 'service'],
        ['Artisanal Kitchen Renovation Blueprint', 'Custom cabinetry layout, marble countertop selection, and bespoke splashbacks.', 420.00, 'service'],
        ['Urban Industrial Loft Renovation', 'Exposed brick accent, custom metal partitions, and curated vintage furnishings.', 390.00, 'service'],
        ['Cozy Bohemian Sunroom & Patio Layout', 'Natural wicker furniture, lush indoor greenery arrangements, and warm textiles.', 260.00, 'service'],
        ['Contemporary Home Office Sanctuary', 'Ergonomic layout with bespoke acoustic oak slatted walls and task lighting.', 310.00, 'service'],
        ['Mediterranean Villa Lounge Concept', 'Limewashed plaster walls, arched doorways, and terracotta stone aesthetics.', 450.00, 'service'],
        ['Modern Coastal Beach House Interior', 'Breezy light oak finishes, linen upholstery, and panoramic natural light concepts.', 380.00, 'service'],
        ['Dark Moody Boutique Powder Room', 'Brass fixtures, deep emerald zellige tiles, and bespoke vanity design.', 220.00, 'service'],
        ['Open-Concept Loft Space Optimization', 'Multifunctional room partitioning with sliding reeded glass doors.', 400.00, 'service'],
        ['Bespoke Walk-in Wardrobe System', 'Custom timber organizer cabinetry with integrated LED warm strip lighting.', 360.00, 'service'],
        ['Mid-Century Modern Living Space Redesign', 'Walnut woodwork, iconic retro furniture sourcing, and statement lighting.', 375.00, 'service'],
        ['Serene Minimalist Nursery Room', 'Non-toxic sustainable materials, soft muted tones, and custom wooden cot.', 240.00, 'service'],
        ['Architectural Entryway & Foyer Makeover', 'Sculptural console, oversized organic mirror, and directional gallery spotlights.', 210.00, 'service'],
        ['Custom Home Library & Reading Nook', 'Floor-to-ceiling built-in bookcases with brass ladder and velvet armchair nook.', 490.00, 'service'],
        ['Modern Farmhouse Kitchen & Pantry', 'Shaker cabinets, open reclaimed wood shelving, and stone farmhouse sink.', 430.00, 'service'],
        ['Zen Meditation & Yoga Studio Room', 'Tatami mats, bamboo panelling, and indirect circadian ambient lighting.', 280.00, 'service'],
        ['Compact Studio Apartment Smart Layout', 'Clever storage solutions, hidden Murphy bed integration, and zoning.', 295.00, 'service'],
        ['Boutique Cafe Commercial Interior Design', 'Inviting hospitality design with bespoke counter bar and acoustic comfort.', 550.00, 'service'],
        ['Organic Modern Primary Bath Spa Suite', 'Freestanding stone soaking tub, fluted timber vanity, and rainfall shower.', 460.00, 'service'],
        ['Rooftop Garden Terrace & Pergola Living', 'Weatherproof teak seating, custom planters, and ambient festoon lighting.', 340.00, 'service'],
        ['Vintage Eclectic Lounge Gallery Living', 'Antique curation, layered Persian rugs, and statement brass chandelier.', 330.00, 'service'],
        ['Contemporary Hallway Art Exhibition Wall', 'Museum-grade directional track lights with picture hanging rails.', 190.00, 'service'],
        ['Luxury Walk-in Wine Cellar Room', 'Climate-controlled smoked glass enclosure with custom walnut wine racks.', 520.00, 'service'],
        ['Sculptural Staircase & Atrium Concept', 'Floating timber treads with frameless glass balustrade and pendant cluster.', 470.00, 'service'],
        ['Cozy Basement Home Cinema Lounge', 'Acoustic plush fabric panels, tiered recliner platform, and star-ceiling.', 510.00, 'service'],
        ['Modern Rustic Dining Room & Bar Nook', 'Live edge slab dining table setting with wrought iron pendant lighting.', 360.00, 'service'],
        ['Nordic Glasshouse Conservatory Interior', 'Iron-framed atrium living with citrus trees and stone paving floor.', 440.00, 'service'],
        ['Full Residence Interior Architecture Package', 'Turnkey 3D renders, CAD floorplans, and comprehensive furniture schedules.', 850.00, 'service'],
    ],

    'custom-furniture' => [
        ['Handcrafted Live Edge Walnut Dining Table', 'Solid black walnut slab with black powder-coated steel spider legs. Seats 8.', 850.00, 'customizable'],
        ['Mid-Century Slatted Oak Credenza', 'Crafted from sustainable white oak with soft-close tambour sliding doors.', 620.00, 'customizable'],
        ['Bespoke Fluted Ash Wood Coffee Table', 'Circular tambour-fluted base with honed Nero Marquina marble top.', 380.00, 'customizable'],
        ['Handwoven Cane & Teak Accent Armchair', 'Ergonomically contoured solid teak frame with double-woven natural cane.', 340.00, 'ready_made'],
        ['Sculptural Curved Boucle Sofa', 'Custom upholstered 3-seater curved lounge sofa in premium cream bouclé.', 1100.00, 'customizable'],
        ['Floating Solid Elm Nightstand (Pair)', 'Minimalist wall-mounted bedside drawers with concealed soft-glide tracks.', 260.00, 'ready_made'],
        ['Architectural Hardwood Bookshelf Unit', 'Asymmetrical open shelving system in oiled European oak.', 540.00, 'customizable'],
        ['Handmade Danish Cord Bench Seat', 'Traditional Scandinavian lacing technique on hand-shaped ash frame.', 290.00, 'ready_made'],
        ['Reclaimed Barnwood Industrial Console', 'Century-old reclaimed timber top set over hand-welded matte steel.', 310.00, 'ready_made'],
        ['Bespoke King Platform Bed with Fluted Headboard', 'Solid walnut construction with integrated floating side ledges.', 950.00, 'customizable'],
        ['Minimalist Travertine & Timber Side Table', 'Monolithic natural honed travertine stone pillar with walnut detail.', 230.00, 'ready_made'],
        ['Craftsman High Back Ergonomic Rocker', 'Heirloom quality steam-bent walnut rocking chair with contoured lumbar.', 580.00, 'customizable'],
        ['Modular Walnut Wall Shelving System', 'Customizable brass rail and shelf modular unit for books and curios.', 460.00, 'customizable'],
        ['Sculptural Pedestal Dining Table in Ash', 'Conical fluted pillar base with beveled round hardwood surface.', 780.00, 'customizable'],
        ['Artisanal Leather & Oak Barstools (Set of 2)', 'Full-grain vegetable-tanned saddle leather over hand-turned oak legs.', 420.00, 'customizable'],
        ['Custom Executive Walnut Desk with Wire Trench', 'Bookmatched walnut top with integrated concealed power management.', 890.00, 'customizable'],
        ['Mid-Century Low-Profile Media Console', 'Acoustic fabric grill doors with vintage brass pull hardware.', 510.00, 'customizable'],
        ['Hand-Carved Sculptural Accent Stool', 'Single block charred cedar log carved into an organic hourglass silhouette.', 190.00, 'ready_made'],
        ['Slatted Teak Outdoor Lounge Chair Set', 'Marine-grade stainless hardware and all-weather Sunbrella cushions.', 670.00, 'customizable'],
        ['Bespoke Entryway Shoe Bench with Coat Rack', 'Solid oak storage bench with vertical slatted coat hook backboard.', 370.00, 'customizable'],
        ['Curved Velvet Chaise Lounge', 'Deep forest green velvet with brushed brass tapered feet.', 720.00, 'customizable'],
        ['Minimalist Glass & Teak Coffee Table', 'Tempered smoked glass floating over interlocking solid teak base.', 340.00, 'ready_made'],
        ['Hand-Joined Maple Kitchen Island Cart', 'End-grain butcher block top with brass casters and storage drawers.', 640.00, 'customizable'],
        ['Bespoke Fluted Vanity Cabinet', 'Curved corner fluted oak bathroom vanity with quartz stone basin cutouts.', 790.00, 'customizable'],
        ['Cantilevered Metal & Leather Accent Chair', 'Sleek chrome tubular frame suspended with thick black saddle hide.', 450.00, 'ready_made'],
        ['Custom Wine Rack Credenza in Cherry Wood', 'Holds 36 bottles with internal stemware racks and velvet silverware tray.', 690.00, 'customizable'],
        ['Modern Farmhouse Extendable Dining Table', 'Hand-rubbed wax finish with butterfly extension leaves for up to 12 guests.', 920.00, 'customizable'],
        ['Japanese Minimalist Low Tea Table (Chabudai)', 'Foldable brass hinges, natural paulownia wood with organic grain.', 210.00, 'ready_made'],
        ['Bespoke Fluted Headboard Wall Panel System', 'Fabric padded vertical slats tailored to exact room width.', 580.00, 'service'],
        ['Custom Handcrafted Wardrobe Armoire', 'White oak double doors with woven rattan inserts and cedar interior.', 1250.00, 'customizable'],
    ],

    'painting-digital-art' => [
        ['Ethereal Golden Horizon Abstract Canvas', 'Textured acrylic and 24k gold leaf on gallery-wrapped heavyweight canvas (40x50 in).', 420.00, 'ready_made'],
        ['Moody Ocean Tempest Oil Painting', 'Impasto oil painting with dynamic wave crests and deep indigo swells.', 380.00, 'ready_made'],
        ['Cyberpunk Neo-Tokyo Cyber Cityscape Print', 'Limited edition archival giclee print signed and numbered by the artist.', 120.00, 'ready_made'],
        ['Bespoke Family Watercolor Portrait Commission', 'Custom hand-painted watercolor portrait based on your favorite photo.', 180.00, 'service'],
        ['Textured Earth Tone Minimalist Diptych', 'Plaster and sand relief texture with raw umber and warm bone tones.', 350.00, 'ready_made'],
        ['Botanical Monstera & Palm Digital Illustration', 'High-res vector art printed on 310gsm museum cotton rag paper.', 85.00, 'ready_made'],
        ['Vibrant Cosmic Nebula Galaxy Oil Painting', 'Celestial swirling nebulae on 36-inch circular stretched canvas.', 290.00, 'ready_made'],
        ['Custom Pet Watercolor Art Painting', 'Expressive loose watercolor portrait of your dog or cat with floral wreath.', 140.00, 'service'],
        ['Architectural City Skyline Ink & Watercolor', 'Fine-line pigment ink with loose atmospheric watercolor washes.', 160.00, 'customizable'],
        ['Modern Japanese Sumi-e Ink Brush Art', 'Traditional carbon black ink on handmade Japanese mulberry washi paper.', 210.00, 'ready_made'],
        ['Abstract Expressionist Neon Energy Canvas', 'Action painting with energetic sweeps of magenta, cobalt, and cadmium yellow.', 330.00, 'ready_made'],
        ['Custom Wedding Couple Portrait Illustration', 'Modern minimalist line art with soft pastel color blocks.', 150.00, 'service'],
        ['Nordic Fjord Landscape at Twilight Oil', 'Palette knife texture capturing the northern light reflections.', 440.00, 'ready_made'],
        ['Retro 80s Synthwave Sunset Art Print', 'Vibrant neon grid vector illustration on satin photographic paper.', 75.00, 'ready_made'],
        ['Custom Hand-Lettered Calligraphy Quote Art', 'Gold ink on deep black handmade deckle edge paper with brass wax seal.', 95.00, 'customizable'],
        ['Textured Wabi-Sabi Neutral Wall Art', 'Multi-layered lime plaster with organic cracks and subtle shadows.', 280.00, 'ready_made'],
        ['Vivid Tropical Toucan Gouache Painting', 'Rich opaque gouache illustration with lush rainforest foliage.', 175.00, 'ready_made'],
        ['Geometric Bauhaus Modernist Print Set (Trio)', 'Bold primary colors with intersecting architectural shapes.', 130.00, 'ready_made'],
        ['Custom House & Home Architectural Portrait', 'Detailed watercolor and ink tribute of your first home or family estate.', 220.00, 'service'],
        ['Mystical Mountain Peak Misty Morning Oil', 'Soft layered atmospheric perspective on deep 1.5-inch canvas.', 360.00, 'ready_made'],
        ['Abstract Flow Fluid Acrylic Pour Art', 'High gloss resin coated marbleized swirls of emerald and gold.', 195.00, 'ready_made'],
        ['Surreal Floating Islands Fantasy Concept', 'Digital matte painting artwork available as metallic canvas print.', 140.00, 'ready_made'],
        ['Minimalist Female Figure Continuous Line Drawing', 'Single continuous line illustration embossed with rose gold foil.', 110.00, 'ready_made'],
        ['Custom Album Artwork & Concept Cover Art', 'Custom digital artwork production tailored for musicians and bands.', 250.00, 'service'],
        ['Still Life with Citrus & Ceramic Jug Oil', 'Classic chiaroscuro lighting on Belgian linen canvas.', 310.00, 'ready_made'],
        ['Abstract Mediterranean Terracotta Landscape', 'Sun-drenched hills and olive groves rendered in warm cubist shapes.', 270.00, 'ready_made'],
        ['Custom Digital Character Concept Design', 'Full-body character turnaround with costume variations for games/stories.', 320.00, 'service'],
        ['Monochrome Charcoal Forest Study', 'Willow charcoal and chalk highlights on toned Stonehenge paper.', 165.00, 'ready_made'],
        ['Mid-Century Modern Sun & Archway Canvas', 'Warm mustard, terracotta, and olive retro color palette.', 180.00, 'ready_made'],
        ['Large Scale Commissioned Corporate Lobby Mural', 'Site-specific canvas mural tailored to corporate brand palette.', 1400.00, 'service'],
    ],

    'pottery-ceramics' => [
        ['Hand-Thrown Speckled Stoneware Dinner Set (4pc)', 'Wheel-thrown plates, bowls, and tumblers in raw unglazed exterior and satin white interior.', 190.00, 'customizable'],
        ['Sculptural Wabi-Sabi Terracotta Flower Vase', 'Asymmetrical coil-built vessel with raw volcanic slip texture.', 145.00, 'ready_made'],
        ['Matcha Ceremony Tea Bowl (Chawan)', 'Raku-fired traditional tea bowl with intentional crackle glaze and gold repair.', 85.00, 'ready_made'],
        ['Hand-Pinched Fluted Ceramic Mug Set (Pair)', 'Tactile ridged stoneware mugs glazed in matte sage green with ergonomic thumb rests.', 60.00, 'ready_made'],
        ['Minimalist Ribbed Porcelain Planter Pot', 'Slip-cast white porcelain with integrated saucer and drainage hole.', 75.00, 'ready_made'],
        ['Large Centerpiece Ceramic Fruit Bowl', 'Hand-carved sgraffito botanical pattern along the interior rim.', 130.00, 'ready_made'],
        ['Handmade Japanese Donabe Cooking Pot', 'High-heat resistant clay casserole pot for braising and rice cooking.', 160.00, 'ready_made'],
        ['Abstract Sculptural Ceramic Totem Candleholder', 'Stackable geometric clay components with raw oxide matte finishes.', 95.00, 'ready_made'],
        ['Bespoke Hand-Carved Ceramic Pendant Light', 'Perforated clay lampshade casting dramatic light patterns onto walls.', 175.00, 'customizable'],
        ['Artisanal Espresso Cup & Saucer Duo', 'Speckled iron-rich clay with satin caramel glaze, holds 3 oz.', 48.00, 'ready_made'],
        ['Hand-Coiled Terracotta Amphora Vessel', 'Classic Mediterranean urn with dual pulled loop handles.', 210.00, 'ready_made'],
        ['Ceramic Pour-Over Coffee Dripper & Server', 'Hand-turned spiral ribbing for optimal coffee brew extraction.', 88.00, 'ready_made'],
        ['Organic Edged Ceramic Serving Platter', 'Slab-rolled stoneware platter glazed with oceanic turquoise crystalline glaze.', 115.00, 'ready_made'],
        ['Mini Ceramic Bud Vases Trio (Collection)', 'Three coordinating miniature vases for single garden wildflowers.', 55.00, 'ready_made'],
        ['Custom Ceramic House Number Tile Plaque', 'Hand-painted oxide numbers on frost-proof high fire stoneware.', 70.00, 'customizable'],
        ['Sculptural Clay Ikebana Flower Frog Dish', 'Shallow dish with fixed kenzan needle base for artistic floral arranging.', 65.00, 'ready_made'],
        ['Hand-Decorated Delftware Blue & White Tile Set', 'Six 4x4 inch cobalt painted tiles inspired by antique Dutch motifs.', 120.00, 'ready_made'],
        ['Earthy Stoneware Ramen Bowl with Chopstick Notch', 'Deep 32oz bowl featuring hand-glazed drip effect and carved rests.', 52.00, 'ready_made'],
        ['Bespoke Ceramic Bathroom Soap Dispenser & Tumbler', 'Waterproof sealed wheel-thrown stoneware with brass pump fixture.', 82.00, 'customizable'],
        ['Large Organic Wave Rim Ceramic Vase', 'Dramatic undulating sculptural rim with chalky white volcanic glaze.', 165.00, 'ready_made'],
        ['Handmade Ceramic Mortar & Pestle', 'Unglazed grooved interior designed for grinding fresh herbs and spices.', 58.00, 'ready_made'],
        ['Porcelain Translucent Candle Luminary', 'Paper-thin porcelain body that glows warmly when illuminated by tea light.', 42.00, 'ready_made'],
        ['Wheel-Thrown Pitcher & Milk Jug', 'Elegant pulled spout and balanced handle for effortless pouring.', 68.00, 'ready_made'],
        ['Custom Commission Ceramic Basin Sink', 'Drop-in or vessel bathroom sink fired to cone 10 for lifelong durability.', 380.00, 'service'],
        ['Textured Clay Incense Burner & Ash Catcher', 'Sculptural stone-like incense holder with minimalist brass insert.', 35.00, 'ready_made'],
        ['Hand-Glazed Crystalline Glaze Studio Bottle', 'Rare zinc silicate crystals blooming organically across the cobalt surface.', 195.00, 'ready_made'],
        ['Ceramic Spoon Rest & Cooking Utensil Crock', 'Speckled cream clay with glossy mustard yellow dipped rim.', 64.00, 'ready_made'],
        ['Set of 4 Textured Tapas Pinch Bowls', 'Thumb-indented miniature bowls for sea salt, olives, and condiments.', 45.00, 'ready_made'],
        ['Handmade Stoneware Butter Keeper (French Bell)', 'Water-seal technology that keeps butter fresh and spreadable at room temp.', 50.00, 'ready_made'],
        ['Custom Ceramic Mug with Personalized Pet Carving', 'Hand-carved pet silhouette and name sgraffito on your favorite mug.', 75.00, 'customizable'],
    ],

    'handmade-crafts' => [
        ['Hand-Poured Soy Wax Botanical Candle (3-Wick)', 'Infused with wild lavender, amber, and dried pressed garden florals.', 45.00, 'ready_made'],
        ['Large Macrame Wall Hanging on Driftwood', 'Intricate bohemian knotwork crafted with 100% recycled cotton rope.', 140.00, 'ready_made'],
        ['Hand-Bound Leather Journal with Deckle Paper', 'Full-grain oiled leather journal with 240 blank cotton watercolor pages.', 68.00, 'customizable'],
        ['Needle-Felted Woodland Fox & Fawn Sculptures', 'Pure merino wool felted with glass eyes and posable wire armatures.', 95.00, 'ready_made'],
        ['Pressed Flower Floating Glass Frame (8x10)', 'Real preserved ferns, pansies, and baby breath in antique brass locket frame.', 55.00, 'ready_made'],
        ['Chunky Hand-Knitted Merino Wool Throw Blanket', 'Ultra-soft arm-knitted blanket in dusty rose (50x60 inches).', 180.00, 'customizable'],
        ['Handmade Cold-Process Artisan Soap Gift Box', 'Set of 6 organic bars made with olive oil, shea butter, and essential oils.', 38.00, 'ready_made'],
        ['Stained Glass Monstera Deliciosa Suncatcher', 'Hand-cut emerald cathedral glass soldered with copper foil patina.', 62.00, 'ready_made'],
        ['Hand-Embroidered Floral Linen Hoop Art', 'Dozens of French knots and satin stitches in a 7-inch beechwood embroidery ring.', 72.00, 'ready_made'],
        ['Custom Wood-Burned Pyrography Cutting Board', 'Organic maple paddle board with customized family crest and date.', 85.00, 'customizable'],
        ['Handwoven Rattan & Seagrass Belly Basket', 'Collapsible basket with reinforced handles for laundry or indoor trees.', 48.00, 'ready_made'],
        ['Resin & Walnut Wood Geode Coaster Set (4pc)', 'Natural walnut slices fused with shimmering sapphire resin and metallic foil.', 52.00, 'ready_made'],
        ['Hand-Carved Wooden Cooking Spoon & Spatula', 'Cherry and walnut kitchen utensils treated with organic beeswax polish.', 44.00, 'ready_made'],
        ['Handmade Beeswax Taper Candles (Set of 6)', '100% pure filtered beeswax with natural honey aroma and cotton wicks.', 34.00, 'ready_made'],
        ['Punch Needle Abstract Rainbow Wall Tapestry', 'Plush looped wool yarn tapestry framed in natural timber.', 88.00, 'ready_made'],
        ['Handcrafted Leather Cardholder Minimalist Wallet', 'Hand-stitched with waxed linen thread using premium Italian buttero leather.', 55.00, 'customizable'],
        ['Mosaic Glass Garden Stepping Stone', 'Frostproof handmade mosaic tile stone featuring a kaleidoscope mandala motif.', 78.00, 'ready_made'],
        ['Quilted Patchwork Heirloom Baby Blanket', 'Soft organic cotton fabrics pieced together with cloud-pattern hand stitching.', 125.00, 'ready_made'],
        ['Custom Monogrammed Wax Seal Stamp with Handle', 'Precision CNC engraved solid brass die with turned rosewood handle.', 42.00, 'customizable'],
        ['Handcrafted Wooden Chess Set & Folding Board', 'Hand-turned sheesham and boxwood chess pieces with weighted felt bases.', 220.00, 'ready_made'],
        ['Beaded Native American Inspired Boho Fringe Earrings', 'Delicate Japanese Miyuki glass seed beads with 14k gold-filled ear wires.', 46.00, 'ready_made'],
        ['Air Dry Clay Incense Holder with Gold Trim', 'Marbled pastel clay dish edged in liquid metallic leaf.', 28.00, 'ready_made'],
        ['Handmade Paper Stationery Set with Botanical Seeds', 'Plantable wildflower seed paper envelopes and letterheads (10 pack).', 32.00, 'ready_made'],
        ['Knitted Alpaca Wool Cable Beanie with Faux Pom', 'Exceptionally warm, lightweight natural alpaca wool knit in oatmeal.', 45.00, 'ready_made'],
        ['Custom Pet Felt Miniature Keepsake', 'True-to-life needle-felted replica of your pet made using their real fur combings.', 160.00, 'service'],
        ['Stained Glass Geometric Terrarium for Succulents', 'Hand-soldered faceted glass dodecahedron planter.', 68.00, 'ready_made'],
        ['Hand-Dyed Shibori Indigo Linen Table Runner', 'Japanese resistance tie-dye technique on pure textured flax linen.', 58.00, 'ready_made'],
        ['Bespoke Leather Camera Strap with Quick Releases', 'Cushioned bridle leather neck strap personalized with initials.', 75.00, 'customizable'],
        ['Decoupage Vintage Oyster Shell Trinket Dish Set', 'Gilded rim shells embellished with vintage botanical toile prints.', 36.00, 'ready_made'],
        ['Handcrafted Birch Bark & Willow Wreath', 'Natural seasonal foraging wreath with preserved eucalyptus and pinecones.', 64.00, 'ready_made'],
    ],

    'jewellery-design' => [
        ['14k Solid Gold Dainty Solitaire Moissanite Ring', 'Brilliant round cut moissanite set in a delicate 1.3mm hammered gold band.', 280.00, 'customizable'],
        ['Raw Herkimer Diamond Crystal Stud Earrings', 'Natural double-terminated quartz crystals set in recycled sterling silver.', 65.00, 'ready_made'],
        ['Hammered Gold Vermeil Chunky Statement Hoop Earrings', 'Lightweight hollow tube design with 18k thick gold plating over silver.', 82.00, 'ready_made'],
        ['Personalized Roman Numeral Bar Necklace', 'Hand-stamped date necklace in sterling silver or 14k gold fill.', 75.00, 'customizable'],
        ['Handcrafted Opal & Moonstone Celestial Ring', 'Ethiopian fire opal flanked by two shimmering rainbow moonstone accents.', 195.00, 'customizable'],
        ['Bespoke Custom Signet Ring with Hand Engraved Crest', 'Heavyweight solid sterling silver signet ring engraved by master craftsman.', 240.00, 'service'],
        ['Baroque Freshwater Pearl Pendant on Paperclip Chain', 'Unique irregular organically shaped pearl on a modern toggle necklace.', 92.00, 'ready_made'],
        ['Turquoise & Sterling Silver Southwestern Cuff Bracelet', 'Kingman mine natural turquoise stone with hand-stamped repousse borders.', 210.00, 'ready_made'],
        ['Minimalist Diamond-Cut Stacking Rings (Set of 3)', 'Three sparkling faceted bands in 14k yellow, rose, and white gold.', 160.00, 'ready_made'],
        ['Art Deco Emerald Cut Green Tourmaline Ring', 'Geometric halo of bead-set lab diamonds encircling deep forest tourmaline.', 390.00, 'customizable'],
        ['Hand-Forged Damascus Steel Wedding Band', 'Swirling high-carbon pattern welded steel with comfort fit rosewood liner.', 220.00, 'customizable'],
        ['Dainty Birthstone Droplet Choker Necklace', 'Faceted genuine gemstones representing each month on 14k gold cable chain.', 68.00, 'customizable'],
        ['Raw Emerald Prongs Statement Cocktail Ring', 'Uncut Colombian natural rough emerald nestled in organically molten brass.', 185.00, 'ready_made'],
        ['Layered Coin Medallion Necklace Duo', 'Antique goddess embossed pendants with vintage curb chain lengths.', 110.00, 'ready_made'],
        ['Handmade Enamel Cloisonne Butterfly Brooch', 'Traditional vitreous glass powder kiln-fired between delicate gold wires.', 145.00, 'ready_made'],
        ['Solid 925 Silver Heavy Byzantine Chain Bracelet', 'Intricate hand-woven jump ring pattern with custom box clasp.', 175.00, 'ready_made'],
        ['Morganite & Rose Gold Engagement Ring Set', 'Soft peachy pink cushion-cut morganite with matching curved diamond tiara band.', 480.00, 'customizable'],
        ['Labradorite Flash Teardrop Wire Wrapped Pendant', 'Blue-green iridescent labradorite wrapped in antiqued copper wire weaves.', 58.00, 'ready_made'],
        ['Chunky Sterling Silver Twist Huggie Hoops', 'Modern twisted sculptural ear hoops with secure clicker closure.', 52.00, 'ready_made'],
        ['Custom Family Tree Fingerprint Disc Necklace', 'Laser engraved actual fingerprint impressions of your loved ones.', 125.00, 'customizable'],
        ['Meteorite & Dinosaur Bone Inlaid Ring Band', 'Authentic Gibeon meteorite shavings and fossilized bone in black titanium.', 310.00, 'customizable'],
        ['Boho Feather Ear Cuff & Chain Attachment', 'Sterling silver engraved feather creeping up the helix with dangling chain.', 48.00, 'ready_made'],
        ['Gothic Black Spinel Rosary Beaded Choker', 'Faceted jet-black gemstones hand-linked with oxidized sterling wire.', 85.00, 'ready_made'],
        ['Geometric Hexagon Moss Agate Ring', 'Forest-like dendritic inclusions in clear quartz, bezel set in yellow gold.', 190.00, 'customizable'],
        ['Handmade Polymer Clay Statement Floral Earrings', 'Lightweight sculpted peony blossoms in muted terracotta and ecru.', 36.00, 'ready_made'],
        ['Solid Gold Threader Earrings with Tiny Moissanite', 'Sleek box chain threader that slips through multiple ear piercings.', 78.00, 'ready_made'],
        ['Artisan Garnet Filigree Cocktail Ring in 925 Silver', 'Deep red Mozambique garnet framed by ornate Victorian filigree coils.', 135.00, 'ready_made'],
        ['Bespoke Bridal Pearl Backdrop Necklace', 'Dramatic pearl chain that drapes gracefully down low-back wedding gowns.', 165.00, 'customizable'],
        ['Textured Molten Nugget Gold Stud Earrings', 'Organic fluid shapes hand-cast using the ancient lost-wax casting method.', 70.00, 'ready_made'],
        ['Custom Coordinate Latitude Longitude Cuff Bracelet', 'Heavy gauge hand-stamped aluminum or copper memorial cuff.', 45.00, 'customizable'],
    ],

    'photography' => [
        ['Fine Art Misty Redwood Forest Archival Print', 'Limited edition panoramic print on heavy Canson Rag Photographique.', 150.00, 'ready_made'],
        ['Golden Hour Coastal Waves Fine Art Photograph', 'Dramatic backlight hitting Atlantic ocean breakers in Maine.', 120.00, 'ready_made'],
        ['Bespoke Editorial Portrait Photography Session', '2-hour studio or outdoor fashion portrait shoot with 15 retouched masters.', 350.00, 'service'],
        ['Architecture & Urban Geometry Monochrome Print', 'High-contrast black & white architectural study of Chicago high-rises.', 110.00, 'ready_made'],
        ['Nordic Aurora Borealis Night Sky Canvas', 'Vibrant green dancing northern lights over snowy Arctic peaks.', 175.00, 'ready_made'],
        ['Custom Brand & Product Photography Package', 'Commercial e-commerce styling, flat lays, and lifestyle imagery (20 assets).', 450.00, 'service'],
        ['Moody Raindrops on Window Bokeh Cityscape', 'Cinematic night neon lights diffused through wet glass in Tokyo.', 95.00, 'ready_made'],
        ['Fine Art Desert Sand Dunes at Sunset Print', 'Sculptural wind-swept ripples in Death Valley at golden hour.', 135.00, 'ready_made'],
        ['Intimate Elopement & Wedding Photography', 'Half-day photo documentary coverage with online gallery & high-res delivery.', 950.00, 'service'],
        ['Macro Dewdrop on Spiderweb Botanical Photo', 'Extreme close-up photography capturing miniature refraction worlds.', 85.00, 'ready_made'],
        ['Aerial Turquoise Coral Reef Drone Photograph', 'Bird-eye perspective of tropical sandbars in the South Pacific.', 160.00, 'ready_made'],
        ['Vintage 35mm Film Grain European Street Scene', 'Shot on Kodak Portra 400 through vintage Leica optics in Paris.', 105.00, 'ready_made'],
        ['Custom Family Outdoor Golden Hour Mini Session', '30-minute lifestyle session capturing joyful candids with your kids.', 220.00, 'service'],
        ['Dramatic Black & White Wildlife Elephant Portrait', 'Close-up texture of elephant skin and tusks in Amboseli Kenya.', 180.00, 'ready_made'],
        ['Moody Autumn Aspen Forest in the Fog', 'Glowing golden aspen leaves shrouded in heavy Rocky Mountain mist.', 140.00, 'ready_made'],
        ['Culinary Gastronomy Food Styling Shoot', 'Professional food photography for restaurant menus and culinary books.', 380.00, 'service'],
        ['Cinematic Midnight Neon Alleyway Print', 'Cyber-inspired atmospheric photograph taken in Shinjuku backstreets.', 115.00, 'ready_made'],
        ['Minimalist Solitary Tree in Snow Field Photograph', 'Stark black and white snow landscape with perfect negative space.', 125.00, 'ready_made'],
        ['Surfer Silhouetted Against Sunset Swell', 'Dramatic backlit water photography from inside the ocean barrel.', 130.00, 'ready_made'],
        ['Fine Art Architecture Acrylic Glass Mount Print', 'Ultra-high gloss acrylic face mount delivering 3D depth and vibrancy.', 260.00, 'ready_made'],
        ['Custom Interior & Real Estate Architectural Shoot', 'HDR blended architectural photography for interior designers & listings.', 320.00, 'service'],
        ['Milky Way Core Over Desert Arches Nightscape', 'Multi-exposure tracked astrophotography of the galactic core.', 195.00, 'ready_made'],
        ['Vibrant Indian Holi Festival of Colors Portrait', 'Explosion of powdered pigments on joyful laughing faces.', 145.00, 'ready_made'],
        ['Custom Newborn Lifestyle Home Photography', 'Gentle unposed newborn session in the cozy comfort of your home nursery.', 280.00, 'service'],
        ['Moody Icelandic Waterfall & Black Sand Beach', 'Long exposure smoothing torrents of water against volcanic basalt.', 155.00, 'ready_made'],
        ['Abstract Reflections in Modern Glass Skyscraper', 'Distorted architectural lines and cloud reflections.', 98.00, 'ready_made'],
        ['Vintage Classic Car at Havana Malecon Print', 'Classic 1950s turquoise Chevy parked against crashing sea spray.', 110.00, 'ready_made'],
        ['Professional LinkedIn & Executive Headshot Package', 'Clean studio lighting, expression coaching, and 3 retouched portraits.', 180.00, 'service'],
        ['Ethereal Mountain Lake Reflection at Dawn', 'Glass-smooth alpine lake mirroring snowy peaks in Banff.', 165.00, 'ready_made'],
        ['Bespoke Commercial Fashion Campaign Production', 'Full day creative direction, lighting, and high-fashion editorial capture.', 1200.00, 'service'],
    ],

    'fashion-clothing' => [
        ['Handmade Pure Linen Relaxed Kimono Robe', 'Pre-washed French flax linen in earthy terracotta with deep pockets.', 145.00, 'ready_made'],
        ['Bespoke Tailored Wool Trench Coat', 'Double-breasted Italian wool overcoat with horn buttons and satin lining.', 420.00, 'customizable'],
        ['Hand-Embroidered Denim Jacket with Floral Back', 'Vintage wash heavy denim jacket embellished with custom embroidery.', 180.00, 'customizable'],
        ['Silk Charmeuse Bias-Cut Slip Dress', '100% mulberry silk gown that drapes fluidly along feminine contours.', 210.00, 'customizable'],
        ['Chunky Hand-Knit Mohair Wool Sweater', 'Cloud-soft loose weave fisherman jumper in natural unbleached cream.', 165.00, 'ready_made'],
        ['Custom Made-to-Measure Linen Trousers', 'High-waisted pleated wide leg pants crafted from organic Belgian flax.', 130.00, 'customizable'],
        ['Artisanal Vegetable-Tanned Leather Moto Jacket', 'Buttery soft lambskin leather jacket with antique brass YKK hardware.', 490.00, 'customizable'],
        ['Hand-Dyed Botanical Silk Scarf (Square)', 'Natural plant dyes from madder root, avocado pits, and marigolds.', 65.00, 'ready_made'],
        ['Organic Cotton Tiered Boho Maxi Skirt', 'Breathable double gauze cotton with elasticated smocked waistband.', 88.00, 'ready_made'],
        ['Custom Tailored Two-Piece Linen Summer Suit', 'Unstructured blazer and matching trousers in crisp oat linen.', 380.00, 'customizable'],
        ['Hand-Crocheted Granny Square Festival Cardigan', 'Colorful retro cotton cardigan hand-crocheted over 40 hours.', 195.00, 'ready_made'],
        ['Bespoke Evening Velvet Wrap Gown', 'Plush silk-blend velvet with cascading side tie and subtle train.', 320.00, 'customizable'],
        ['Upcycled Vintage Quilt Chore Jacket', 'One-of-a-kind boxy jacket made from 1940s hand-stitched patchwork quilt.', 240.00, 'ready_made'],
        ['Japanese Raw Selvedge Denim Jeans (Custom Inseam)', '14oz unsanforized cotton denim crafted on antique shuttle looms.', 175.00, 'customizable'],
        ['Handwoven Pashmina Cashmere Shawl Wrap', 'Featherlight 100% Himalayan cashmere woven on traditional wooden looms.', 150.00, 'ready_made'],
        ['Minimalist Organic Cotton Jumpsuit with Belt', 'Utility-inspired boiler suit with tortoiseshell button front.', 115.00, 'ready_made'],
        ['Bespoke Bridal Veil with Hand-Sewn Lace Appliques', 'Cathedral length illusion tulle edged with French Chantilly lace.', 260.00, 'service'],
        ['Hand-Block-Printed Cotton Sundress with Ruffles', 'Hand-stamped in Jaipur using traditional teak blocks and vegetable ink.', 95.00, 'ready_made'],
        ['Merino Wool Cable Knit Cardigan with Wooden Buttons', 'Heirloom Irish style cardigan in natural peat green.', 170.00, 'ready_made'],
        ['Custom Tailored Formal Tuxedo Dinner Jacket', 'Peak lapels faced in duchess satin with bespoke monogrammed lining.', 460.00, 'customizable'],
        ['Silk Satin Pajama Set with Contrast Piping', 'Luxurious button-down shirt and relaxed drawstring pajama pants.', 135.00, 'customizable'],
        ['Heavyweight Organic Hemp Fleece Hoodie', 'Sustainable thermal fleece pullover built for decades of wear.', 98.00, 'ready_made'],
        ['Handmade Leather Greek Gladiator Sandals', 'Natural tan leather soles with adjustable straps that mold to your foot.', 85.00, 'customizable'],
        ['Bohemian Embroidered Peasant Blouse', 'Delicate cross-stitch embroidery on crisp unbleached cotton batiste.', 78.00, 'ready_made'],
        ['Bespoke Leather Corset Belt with Lacing', 'Sculpted vegetable leather underbust corset that cinches dresses.', 110.00, 'customizable'],
        ['Water-Resistant Waxed Canvas Field Jacket', 'British Millerain waxed cotton with soft corduroy collar and plaid lining.', 290.00, 'ready_made'],
        ['Breezy Cotton Muslin Beach Cover-Up Kaftan', 'Semi-sheer relaxed tunic with deep V-neck and fringed hems.', 62.00, 'ready_made'],
        ['Hand-Knitted Chunky Wool Balaclava Hood', 'Cozy ribbed knit hood that doubles as a neck warmer in alpine winds.', 48.00, 'ready_made'],
        ['Custom Couture Prom or Red Carpet Dress Design', 'Full custom design from initial sketch to muslin fitting and final gown.', 750.00, 'service'],
        ['Artisanal Cotton Terry Cloth Resort Shirt', 'Vintage 70s cabana style polo shirt perfect for poolside lounging.', 72.00, 'ready_made'],
    ],

    'graphic-design' => [
        ['Comprehensive Brand Identity & Logo Suite', 'Primary logo, secondary marks, color palette, typography guidelines, and icons.', 380.00, 'service'],
        ['Bespoke Minimalist Packaging Box Design', 'Die-cut templates, 3D photorealistic mockups, and print-ready vector files.', 290.00, 'service'],
        ['Custom Vector Illustration Set for Website (5 Assets)', 'Unique, on-brand tech or editorial illustrations tailored to your company.', 220.00, 'service'],
        ['Luxury Hot Foil Stamped Business Card Design', 'Minimalist typography with blind deboss and metallic foil specifications.', 95.00, 'service'],
        ['Modern SaaS UI/UX Landing Page Design in Figma', 'High-fidelity responsive web layout with auto-layout design system.', 420.00, 'service'],
        ['Artistic Album Vinyl Gatefold & Sleeve Artwork', 'Front, back, insert lyric booklet, and vinyl center label vector graphics.', 260.00, 'service'],
        ['Custom Typography & Hand-Crafted Display Font', 'Complete OTF/TTF font with uppercase, lowercase, numerals, and glyphs.', 350.00, 'service'],
        ['Vintage Retro Mascot Character Illustration', 'Hand-inked 1930s rubber-hose cartoon mascot with badge lockups.', 160.00, 'service'],
        ['Restaurant Menu Design & Brand Collateral', 'Elegant menu layouts, drink lists, table tents, and social promo templates.', 180.00, 'service'],
        ['Social Media Marketing Branding Kit (30 Templates)', 'Canva & Figma editable templates for Instagram posts, stories, and pins.', 140.00, 'service'],
        ['Corporate Pitch Deck & Presentation Template (20 Slides)', 'Investor-ready PowerPoint and Keynote deck with custom charts.', 210.00, 'service'],
        ['Botanical Craft Beer Can Label Vector Design', 'Full wrap label with metallic substrate layers and barcode integration.', 175.00, 'service'],
        ['Custom Hand-Drawn Monogram & Crest Logo', 'Interlocking letters ideal for luxury brands, weddings, and estates.', 130.00, 'service'],
        ['Infographic & Data Visualization Design', 'Transform complex statistics into clear, captivating visual stories.', 190.00, 'service'],
        ['Book Cover & Dust Jacket Typography Design', 'Typography-first commercial book cover for Amazon KDP and print.', 230.00, 'service'],
        ['Custom Apparel & Merch Graphic Screenprint Design', 'Separated spot color vector artwork ready for screenprint and DTG.', 145.00, 'service'],
        ['Custom Iconography System (30 Bespoke Icons)', 'Pixel-perfect SVG icon set built on a consistent 24px grid.', 160.00, 'service'],
        ['Editorial Magazine Layout & Typography Styling', 'Spreads, master pages, pull quotes, and editorial hierarchy.', 310.00, 'service'],
        ['Animated Brand Logo Intro Lottie / MP4', 'Sleek motion graphics reveal of your existing logo for video headers.', 170.00, 'service'],
        ['Hand-Crafted Coffee Bag & Roast Label Design', 'Craft paper pouch packaging with thermal sticker labeling zones.', 185.00, 'service'],
        ['Custom Event Invitation Suite (Wedding / Gala)', 'Invitation card, RSVP, details insert, and envelope envelope liner.', 150.00, 'service'],
        ['E-Commerce Email Newsletter Design (Klaviyo / Mailchimp)', 'High-converting responsive email layout with promotional banners.', 125.00, 'service'],
        ['Isometric 3D Vector Cityscape & Tech Scene', 'Intricate geometric 3D world illustration for web hero sections.', 280.00, 'service'],
        ['Custom Tarot Card Deck Graphic Design', '78 cards with intricate border engraving style and guidebook layout.', 650.00, 'service'],
        ['Vehicle Wrap Commercial Graphic Design', 'Template-accurate van or truck graphics formatted to real vehicle blueprints.', 240.00, 'service'],
        ['Brand Guidelines Brand Book PDF Document (24 Pages)', 'Comprehensive manual on logo usage, tone of voice, colors, and imagery.', 320.00, 'service'],
        ['Custom Twitch & Streamer Overlay Package', 'Animated webcam borders, intermission screens, stinger transitions, and badges.', 165.00, 'service'],
        ['Artisanal Candle Label & Fragrance Box Packaging', 'Clean Scandinavian typography with gold foil accents.', 155.00, 'service'],
        ['Custom Vector Map Illustration for Tourism / Resort', 'Hand-drawn pictorial map with key landmark pins and compass rose.', 275.00, 'service'],
        ['Complete Startup Visual Rebrand Package', 'Comprehensive strategy, full asset generation, and collateral design.', 850.00, 'service'],
    ],

    'home-decor' => [
        ['Handwoven Moroccan Wool Rug (Beni Ourain 5x7)', 'Plush 100% Atlas Mountain wool with traditional geometric diamond motifs.', 450.00, 'ready_made'],
        ['Sculptural Organic Wavy Floor Standing Mirror', 'Full-length wood frame upholstered in textured ivory boucle fabric.', 380.00, 'ready_made'],
        ['Mid-Century Modern Brass & Milk Glass Chandelier', 'Sputnik-inspired 6-arm ceiling pendant with warm dimmable illumination.', 290.00, 'ready_made'],
        ['Handcrafted Ceramic Arch Table Lamp with Linen Shade', 'Matte terracotta base with natural woven oatmeal linen shade.', 165.00, 'ready_made'],
        ['Abstract Textured Wall Plaster Canvas Art (Set of 2)', '30x40 inch diptych with tactile sweeping curves in warm limestone tones.', 260.00, 'ready_made'],
        ['Bespoke Custom Linen Blackout Drapery (Pair)', 'Heavy Belgian linen curtains with privacy blackout thermal backing.', 310.00, 'customizable'],
        ['Handmade Wooden Floating Wall Shelf Set (Trio)', 'Solid black walnut shelves with concealed heavy-duty steel brackets.', 140.00, 'ready_made'],
        ['Sculptural Solid Travertine Stone Bookends', 'Heavy honed Roman travertine blocks with organic bevelled arches.', 95.00, 'ready_made'],
        ['Chunky Hand-Tufted Wool Accent Pillow Cover', 'Tactile boucle and loop pile geometric pillow in rust and cream (20x20).', 55.00, 'ready_made'],
        ['Modern Minimalist Candelabra in Matte Black Iron', 'Holds 5 taper candles with sculptural undulating architectural arms.', 78.00, 'ready_made'],
        ['Hand-Poured Terrazzo Concrete Incense & Catch-all Tray', 'Colorful recycled marble chips suspended in sealed white concrete.', 42.00, 'ready_made'],
        ['Artisanal Ceramic Wall Sconce Light Fixture', 'Clay bowl light diffuser casting indirect halo illumination onto wall.', 135.00, 'ready_made'],
        ['Large Woven Seagrass Floor Storage Basket with Lid', 'Handmade hamper with sturdy integrated handles and snug lid.', 85.00, 'ready_made'],
        ['Solid Brass Vintage Botanical Cabinet Knobs (Set of 4)', 'Cast brass ginkgo leaf drawer pulls with matching mounting screws.', 48.00, 'ready_made'],
        ['Hand-Knotted Macrame Hanging Plant Holder', 'Extra-long cotton hanger suspending a 7-inch terracotta pot.', 36.00, 'ready_made'],
        ['Sculptural Wood Pillar Candle Holders (Set of 3)', 'Turned solid oak pillars in varying heights (8, 10, and 12 inches).', 82.00, 'ready_made'],
        ['Bespoke Stained Glass Geometric Sunburst Mirror', 'Hand-soldered amber and fluted glass border surrounding round mirror.', 195.00, 'customizable'],
        ['Handmade Stoneware Ceramic Wall Hanging Mask', 'Abstract decorative tribal-inspired mask with raw oxide accents.', 110.00, 'ready_made'],
        ['Organic Wavy Edge Marble Catchall Bowl', 'Hand-carved from a single block of Calacatta Viola violet marble.', 145.00, 'ready_made'],
        ['Custom Framed Pressed Botanical Fern Specimens', 'Pressed wild woodland ferns mounted between double pane floating glass.', 125.00, 'customizable'],
        ['Handwoven Chunky Jute & Cotton Braided Rug (6x9)', 'Durable natural fiber rug with soft cotton interwoven for comfort.', 240.00, 'ready_made'],
        ['Ribbed Fluted Glass Ultrasonic Aroma Diffuser', 'Warm ambient amber LED glow with whisper-quiet cool mist aroma.', 65.00, 'ready_made'],
        ['Minimalist Aluminum Wall Clock with Walnut Hands', 'Silent sweep quartz movement without ticking on powder-coated dial.', 72.00, 'ready_made'],
        ['Textured Boucle Ottoman Pouf Footstool', 'Firm foam core upholstered in durable stain-resistant cloud bouclé.', 130.00, 'ready_made'],
        ['Hand-Hammered Copper Fireplace Log Holder', 'Heavy gauge solid copper bucket with forged iron bail handle.', 175.00, 'ready_made'],
        ['Abstract Wooden Wall Relief Sculpture', 'Layered CNC and hand-carved oak panels creating light and shadow play.', 280.00, 'ready_made'],
        ['Artisanal Linen & Feather Down Throw Pillows (Pair)', 'Overstuffed 90/10 feather down inserts with stone-washed linen cases.', 95.00, 'ready_made'],
        ['Antique Style Brass Hanging Picture Frames (Set of 3)', 'Vintage locket-style hanging glass frames on delicate chains.', 58.00, 'ready_made'],
        ['Handmade Rattan Wicker Pendant Light Shade', 'Dome-shaped basketweave lampshade creating warm lattice shadows.', 115.00, 'ready_made'],
        ['Custom Personalized Family Wooden Welcome Door Sign', 'Round 18-inch stained birch with 3D laser-cut lettering and faux greenery.', 68.00, 'customizable'],
    ],
];

// Fetch category IDs
$cats = $pdo->query("SELECT slug, id FROM categories")->fetchAll(PDO::FETCH_KEY_PAIR);

// Fetch creator IDs
$creators = $pdo->query("SELECT id FROM users WHERE role = 'creator'")->fetchAll(PDO::FETCH_COLUMN);
if (empty($creators)) {
    $creators = [998, 999, 1001];
}

echo "Found " . count($cats) . " categories and " . count($creators) . " creators.\n";

// Clear existing sample listings to avoid duplicates and ensure clean 30 per category
echo "Resetting listings table...\n";
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
$pdo->exec("TRUNCATE TABLE wishlists;");
$pdo->exec("TRUNCATE TABLE listings;");
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

$insert_stmt = $pdo->prepare("
    INSERT INTO listings (creator_id, category_id, title, description, price, listing_type, image_url, views, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? DAY))
");

$total_inserted = 0;

foreach ($category_items as $slug => $items) {
    if (!isset($cats[$slug])) {
        echo "Warning: Category slug $slug not found in DB!\n";
        continue;
    }
    $cat_id = $cats[$slug];
    $pool = $CATEGORY_IMAGE_POOLS[$slug] ?? [];

    echo "Inserting 30 listings for category: $slug (ID $cat_id)...\n";

    foreach ($items as $idx => $item) {
        $title = $item[0];
        $desc  = $item[1];
        $price = $item[2];
        $type  = $item[3];

        // Specific image from pool
        $image_url = $pool[$idx % count($pool)] ?? 'https://images.unsplash.com/photo-1579783902614-a3fb3927b6a5?w=700&q=82&auto=format&fit=crop';

        // Rotate creator
        $creator_id = $creators[$idx % count($creators)];

        // Spread created_at slightly
        $day_offset = rand(0, 30);
        $views = rand(15, 450);

        $insert_stmt->execute([
            $creator_id,
            $cat_id,
            $title,
            $desc,
            $price,
            $type,
            $image_url,
            $views,
            $day_offset
        ]);
        $total_inserted++;
    }
}

echo "Successfully inserted $total_inserted listings across 10 categories (30 per category)!\n";

// Also update categories.image_url to use the 1st stunning image from each category pool
$cat_update = $pdo->prepare("UPDATE categories SET image_url = ? WHERE slug = ?");
foreach ($CATEGORY_IMAGE_POOLS as $slug => $pool) {
    $first_img = $pool[0];
    $cat_update->execute([$first_img, $slug]);
}
echo "Updated categories table with high-res Unsplash category preview images!\n";
