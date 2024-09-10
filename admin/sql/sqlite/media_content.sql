-- Media

	-- get all media

	PROCEDURE getAll(
		IN media_id INT,
		IN language_id INT,
		IN start INT,
		IN limit INT,
		OUT fetch_all, 
		OUT fetch_one,
	)
	BEGIN
		-- media_content
		SELECT media.*,media_content.*

			@IF !empty(:content) 
			THEN			
				,content
			END @IF
		
			FROM media_content AS media
		WHERE 1 = 1
		
		@IF !empty(:media_id) 
		THEN			
			AND media.media_id = :media_id
		END @IF		
			
		@IF !empty(:language_id) 
		THEN			
			AND media.language_id = :language_id
		END @IF
		
		@IF !empty(:limit) 
		THEN			
			@SQL_LIMIT(:start, :limit)
		END @IF
		;
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(media.media_id, media_content) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get media

	PROCEDURE get(
		IN media_id INT,
		IN language_id INT,
		IN file CHAR,
		OUT fetch_row, 
	)
	BEGIN
		-- media_content
		SELECT _.*, media_content.*
			FROM media as _ 
			LEFT JOIN media_content ON (media_content.media_id = _.media_id)
		WHERE 
			1 = 1
	
			@IF !empty(:media_id) 
			THEN			
				AND _.media_id = :media_id
			END @IF			
			
			@IF !empty(:language_id) 
			THEN			
				AND media_content.language_id = :language_id
			END @IF
			
			@IF !empty(:file) 
			THEN			
				AND _.file = :file
			END @IF
			
		LIMIT 1
	END
	
	-- get media_content

	PROCEDURE getContent(
		IN media_id INT,
		IN file CHAR,
		OUT fetch_all, 
	)
	BEGIN
		-- media_content
		SELECT _.*, media_content.*
			FROM media as _ 
			LEFT JOIN media_content ON (media_content.media_id = _.media_id)
		WHERE 
			1 = 1
	
			@IF !empty(:media_id) 
			THEN			
				AND _.media_id = :media_id
			END @IF			
			
			@IF !empty(:file) 
			THEN			
				AND _.file = :file
			END @IF
	END
	
	-- add media_content

	PROCEDURE add(
		IN media ARRAY,
		IN media_content ARRAY,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:media  = @FILTER(:media, media)
		
		INSERT INTO media 
			
			( @KEYS(:media) )
			
	  	VALUES ( :media );

		:media_content  = @FILTER(:media_content, media_content, false, true)

		@EACH(:media_content) 
			INSERT INTO media_content 
		
				( @KEYS(:each), media_id)
			
			VALUES ( :each, @result.media);
	END
	
	-- edit media_content
	CREATE PROCEDURE edit(
		IN media ARRAY,
		IN media_content ARRAY,
		IN media_id INT,
		IN file CHAR,
		OUT affected_rows,
		OUT affected_rows
	)
	BEGIN

		:media_content  = @FILTER(:media_content, media_content, false, true)
		
		@EACH(:media_content) 
			INSERT INTO media_content 
		
				( @KEYS(:each), media_id)
			
			VALUES ( :each, :media_id)
			ON DUPLICATE KEY UPDATE @LIST(:each);

		-- allow only table fields and set defaults for missing values
		@FILTER(:media, media)
	
		@IF !empty(:media) 
		THEN
			UPDATE media 
				
				SET @LIST(:media) 
				
			WHERE media_id = :media_id
		END @IF;

	END
	
	-- delete media_content

	PROCEDURE delete(
		IN media_id ARRAY,
		IN file ARRAY,
		OUT affected_rows
	)
	BEGIN
		-- media_content
		
		DELETE FROM media_content WHERE 
			
			@IF !empty(:media_id) 
			THEN			
				media_id IN (:media_id)
			END @IF

			@IF !empty(:file) 
			THEN			
				media_id IN (SELECT media_id FROM media WHERE file IN (:file))
			END @IF;

		-- media
		
		DELETE FROM media WHERE 

			@IF !empty(:media_id) 
			THEN			
				media_id IN (:media_id)
			END @IF

			@IF !empty(:file) 
			THEN			
				file IN (:file)
			END @IF;
	
	END
