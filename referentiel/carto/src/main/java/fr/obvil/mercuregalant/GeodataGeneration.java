package fr.obvil.mercuregalant;

import java.io.FileNotFoundException;
import java.io.PrintWriter;
import java.util.Map;
import java.util.Map.Entry;

import org.geojson.Feature;
import org.geojson.FeatureCollection;
import org.geojson.Point;

import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.databind.ObjectMapper;

/**
 * 
 * @author Carmen Brando (Labex OBVIL - Univ. Paris-Sorbonne - UPMC LIP6)
 * 
 *         Produces a Javascript file containing GEOJSON data obtained from the Place taxonomy.
 * 
 */
public class GeodataGeneration {
	
	/**
	 * Transforms geodata obtained from the place taxonomy into a Javascript file with the corresponding GeoJSON
	 * for visualization purposes.
	 * 
	 * @param toponyms
	 * @param outgeojson
	 */
	public static void toGeoJSON(Map<String, Map<String, String>> toponyms,
			String outgeojson) {
		FeatureCollection featureCollection = new FeatureCollection();
		for (String placeOrigName : toponyms.keySet()) {
			Feature feat = new Feature();
			Point p = new Point(Double.parseDouble(toponyms.get(placeOrigName)
					.get("lon")), Double.parseDouble(toponyms
					.get(placeOrigName).get("lat")));
			feat.setGeometry(p);
			for (Entry<String, String> prop : toponyms.get(placeOrigName)
					.entrySet()) {
				if (!prop.getKey().equalsIgnoreCase("lat")
						&& !prop.getKey().equalsIgnoreCase("lon")) {
					feat.setProperty(prop.getKey(), prop.getValue());
				}
			}
			feat.setProperty("date", "1910"); // TODO temporary, get from onto
			featureCollection.add(feat);
		}
		try {
			PrintWriter out = new PrintWriter(outgeojson);
			String json = new ObjectMapper()
					.writeValueAsString(featureCollection);
			out.println("var geoData = "); 
			out.println(json);
			out.println(";");
			out.close();
			//also produce the place taxonomy in geojson
		} catch (JsonProcessingException e) {
			e.printStackTrace();
		} catch (FileNotFoundException e) {
			e.printStackTrace();
		}
	}


}
