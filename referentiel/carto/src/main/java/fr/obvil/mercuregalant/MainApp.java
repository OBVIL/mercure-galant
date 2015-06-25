package fr.obvil.mercuregalant;

import java.io.FileInputStream;
import java.io.IOException;
import java.io.InputStream;
import java.util.Map;
import java.util.Properties;

/**
 * 
 * @author Carmen Brando (Labex OBVIL - Univ. Paris-Sorbonne - UPMC LIP6)
 * 
 *         Main class to launch the application for producing a GeoJson
 *         representation of the geographic information described in Mercure
 *         Galant's taxonomy (OWL/XML - .owx). In particular, place individuals
 *         contain Lat/Lon information in the widely-used World geodetic system
 *         WGS84.
 */
public class MainApp {

	public static void main(String[] args) {

		try {
			// checking input parameters
			if (args[0].length() != 0) {
				System.out.println("usage is: java -jar geoApp.jar");
			}
			// reading main parameters
			Properties prop = new Properties();
			InputStream input = new FileInputStream("config.properties");
			prop.load(input);
			String outGeoJson = prop.getProperty("outGeoJson");

			String ontologyFileOWL = prop.getProperty("ontologyFileOWL");
			String individualOntClass = prop.getProperty("individualOntClass");
			String individualArtOntClass = prop
					.getProperty("individualArtOntClass");
			String nSonto = prop.getProperty("NSonto");
			String prefixObvil = prop.getProperty("prefixObvil");
			if (ontologyFileOWL.endsWith(".owl")) {
				// Load ontology and get place names
				Map<String, Map<String, String>> toponyms = PlaceTaxonomyProcessing
						.processPlaceTaxonomy(ontologyFileOWL,
								individualOntClass, individualArtOntClass,
								nSonto, prefixObvil);
				if (toponyms != null) {
					// create geodata in GeoJSON for creating Web map
					GeodataGeneration.toGeoJSON(toponyms, outGeoJson);
					System.out.println("Finished");
				} else {
					System.out.println("Ontology is not correctly defined");
				}
			} else {
				System.out.println("Bad ontology file format");
			}

		} catch (IOException e1) {
			System.out
					.println("Please provide a parameter file config.properties");
			e1.printStackTrace();
		}
	}

}
