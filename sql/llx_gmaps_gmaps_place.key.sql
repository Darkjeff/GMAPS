-- Copyright (C) ---Put here your own copyright and developer email---
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see https://www.gnu.org/licenses/.


-- BEGIN MODULEBUILDER INDEXES
ALTER TABLE llx_gmaps_gmaps_place ADD INDEX idx_gmaps_gmaps_place_rowid (rowid);
ALTER TABLE llx_gmaps_gmaps_place ADD INDEX idx_gmaps_gmaps_place_ref (ref);
ALTER TABLE llx_gmaps_gmaps_place ADD INDEX idx_gmaps_gmaps_place_fk_soc (fk_soc);
ALTER TABLE llx_gmaps_gmaps_place ADD INDEX idx_gmaps_gmaps_place_fk_project (fk_project);
ALTER TABLE llx_gmaps_gmaps_place ADD CONSTRAINT llx_gmaps_gmaps_place_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid);
ALTER TABLE llx_gmaps_gmaps_place ADD INDEX idx_gmaps_gmaps_place_status (status);
ALTER TABLE llx_gmaps_gmaps_place ADD INDEX idx_gmaps_gmaps_place_fk_gmaps_activity (fk_gmaps_activity);
-- END MODULEBUILDER INDEXES

--ALTER TABLE llx_gmaps_gmaps_place ADD UNIQUE INDEX uk_gmaps_gmaps_place_fieldxy(fieldx, fieldy);

--ALTER TABLE llx_gmaps_gmaps_place ADD CONSTRAINT llx_gmaps_gmaps_place_fk_field FOREIGN KEY (fk_field) REFERENCES llx_gmaps_myotherobject(rowid);

