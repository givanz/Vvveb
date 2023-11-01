-- Settings

	-- get one setting

	CREATE PROCEDURE getSetting(
		IN namespace CHAR,
		IN key CHAR,
		IN site_id INT,
		
		OUT fetch_one,
	)
	BEGIN

		SELECT value
            FROM setting AS _
		WHERE _.`key` = :key 
		
		@IF !empty(:namespace) 
		THEN 
			AND _.namespace = :namespace
		END @IF		
		
		@IF !empty(:site_id) 
		THEN 
			AND _.site_id = :site_id
		END @IF		
		;
		
	END
    
	CREATE PROCEDURE setSetting(
		IN namespace CHAR,
		IN key CHAR,
		IN value CHAR,
        IN site_id INT,
		
		OUT insert_id
	)
	BEGIN

        INSERT INTO setting
            (namespace, `key`, value, site_id)
        
        VALUES ( :namespace, :key, :value, :site_id )
        
        ON DUPLICATE KEY 
            UPDATE value = values(value);
		
	END
    
	CREATE PROCEDURE delete(
		IN namespace CHAR,
		IN key CHAR,
	)
	BEGIN

        DELETE FROM 
            setting 
        WHERE `key` = :key;
		
	END

    CREATE PROCEDURE getSettings(
		IN namespace CHAR,
		IN key ARRAY,
		IN site_id INT,
		OUT fetch_all,
	)
	BEGIN

		SELECT namespace, `key`, value
            FROM setting AS _
		WHERE 1 = 1
		
		@IF !empty(:namespace) 
		THEN 
			AND _.namespace = :namespace
		END @IF	
		
		@IF !empty(:key) 
		THEN 
			AND _.`key`IN (:key)
		END @IF	
		
		@IF !empty(:site_id) 
		THEN 
			AND _.site_id = :site_id
		END @IF	
		;
		
	END    
    
    
	CREATE PROCEDURE setSettings(
		IN namespace CHAR,
		IN settings ARRAY,
		IN site_id INT,
	)
	BEGIN

        INSERT INTO setting
            (namespace, `key`, value, site_id)
        
		-- @EACH(:settings) 
			VALUES (namespace, :each, :site_id)
		-- END @EACH	
		
        -- @VALUES(:settings) --@VALUES expands the array to the following expression
        --    ( :settings.each.`key`, :settings.each.value, :site_id )
        
        ON DUPLICATE KEY 
            UPDATE value = VALUES(value);
		
	END
    
	CREATE PROCEDURE deleteSettings(
		IN namespace CHAR,
		IN keys ARRAY,
		IN site_id,
	)
	BEGIN

        DELETE FROM 
            setting 
        WHERE `key` IN (:keys) 
		
		@IF !empty(:namespace) 
		THEN 
			AND _.namespace = :namespace
		END @IF	

		@IF !empty(:site_id) 
		THEN 
			AND _.site_id = :site_id
		END @IF	
		;		
		
	END    
