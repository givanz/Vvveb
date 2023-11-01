-- Setting contents

	-- get one setting content


    CREATE PROCEDURE get(
		IN key ARRAY,
		IN site_id INT,
		IN language_id INT,
	)
	BEGIN

		SELECT "key", value
            FROM setting_content AS _
		WHERE _.keyIN (:key)
		
		@IF !empty(:language_id) 
		THEN 
			AND _.site_id = :site_id
		END @IF	

		@IF !empty(:language_id) 
		THEN 
			AND _.language_id = :language_id
		END @IF	
		
	END    
    
    
	CREATE PROCEDURE _set(
		IN key CHAR,
		IN value CHAR,
        IN site_id INT,
		IN language_id INT,
		
		OUT insert_id
	)
	BEGIN

        INSERT INTO setting_content
            ("key", value, site_id, language_id)
        
        VALUES ( :key, :value, :site_id , :language_id )
        
        ON DUPLICATE KEY 
            UPDATE  value = VALUES(value);
		
	END
    

    
	CREATE PROCEDURE set(
		IN setting_content ARRAY,
		IN site_id INT,
		IN language_id INT,
	)
	BEGIN

        INSERT INTO setting_content
            ("key", value, site_id, language_id)
        
		-- @EACH(:setting_contents) 
			VALUES ( :each, :site_id, :language_id)
		-- END @EACH	
		
        -- @VALUES(:setting_contents) --@VALUES expands the array to the following expression
        --    ( :setting_contents.each.key, :setting_contents.each.value, :site_id )
        
        ON DUPLICATE KEY 
            UPDATE value = VALUES(value);
		
	END
    
	CREATE PROCEDURE delete(
		IN keys ARRAY,
		IN site_id,
		IN language_id INT,
	)
	BEGIN

        DELETE FROM 
            setting_content 
        WHERE "key" IN (:keys) AND site_id = :site_id AND language_id = :language_id;
		
	END    
