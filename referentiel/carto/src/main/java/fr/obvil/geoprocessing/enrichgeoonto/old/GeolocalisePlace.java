package fr.obvil.geoprocessing.enrichgeoonto.old;

import java.io.FileNotFoundException;
import java.io.FileWriter;
import java.io.IOException;
import java.io.UnsupportedEncodingException;
import java.util.ArrayList;
import java.util.Iterator;
import java.util.List;
import java.util.Map;
import java.util.TreeMap;

import org.geonames.Toponym;
import org.geonames.ToponymSearchCriteria;
import org.geonames.ToponymSearchResult;
import org.geonames.WebService;

import com.hp.hpl.jena.query.Query;
import com.hp.hpl.jena.query.QueryExecution;
import com.hp.hpl.jena.query.QueryExecutionFactory;
import com.hp.hpl.jena.query.QueryFactory;
import com.hp.hpl.jena.query.QuerySolution;
import com.hp.hpl.jena.query.ResultSet;

/**
 * @author Carmen Brando
 * 
 */
public class GeolocalisePlace {

	/**
	 * Given the list of non-geolocalised and normalised places,
	 * it uses external sources (so far, GeoNames) to calculate location propositions 
	 * and present them to the user.
	 * 
	 * @return success or failure
	 */
	public static List<Toponym> loadGeoCoordinatesFromGeonames(List<String> places) {
		try {
			List<Toponym> results = new ArrayList<Toponym>();
			String usernamegeonames = "humanum";
			WebService.setUserName(usernamegeonames);
			for (int count = 0; count < places.size(); count++) {
				ToponymSearchCriteria searchCriteria = new ToponymSearchCriteria();
				searchCriteria.setQ(places.get(count).toLowerCase()); 
				//The search is executed over all fields (place name, country name, admin names, etc)
				searchCriteria.setMaxRows(1);
				ToponymSearchResult searchResult;
				searchResult = WebService.search(searchCriteria);
				for (Toponym toponym : searchResult.getToponyms()) {
					if (toponym != null) {
						if (toponym.getFeatureClass() != null) {
							if (toponym.getFeatureClass().name().equalsIgnoreCase("P") 
									|| toponym.getFeatureClass().name().equalsIgnoreCase("A")) {
								results.add(toponym);	
							}
						}
					}
				}
			}
			return  results;
		} catch (Exception e) {
			e.printStackTrace();
			return null;
		}
	}

	public static List<Toponym> loadGeoCoordinatesFromGeonames(String placeName) {
		try {
			List<Toponym> results = new ArrayList<Toponym>();
			String usernamegeonames = "humanum";
			WebService.setUserName(usernamegeonames);
			ToponymSearchCriteria searchCriteria = new ToponymSearchCriteria();
			searchCriteria.setQ(placeName.toLowerCase()); 
			//The search is executed over all fields (place name, country name, admin names, etc)
			searchCriteria.setMaxRows(1);
			ToponymSearchResult searchResult;
			searchResult = WebService.search(searchCriteria);
			for (Toponym toponym : searchResult.getToponyms()) {
				if (toponym != null) {
					if (toponym.getFeatureClass() != null) {
						if (toponym.getFeatureClass().name().equalsIgnoreCase("P") 
								|| toponym.getFeatureClass().name().equalsIgnoreCase("A")) {
							results.add(toponym);	
						}
					}
				}
			}
			return  results;
		} catch (Exception e) {
			e.printStackTrace();
			return null;
		}
	}

	public static List<Map<String, String>> loadGeoCoordinatesFromGeosparqlEndPoint(String placeName) {

		List<Map<String, String>> places = new ArrayList<Map<String, String>>();
		String queryStr = "PREFIX gn: <http://www.geonames.org/ontology#> "
				+ "PREFIX co: <http://www.geonames.org/countries/#> "
				+ "PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#> "
				+ "SELECT  distinct ?place ?name ?lat ?lon ?class"
				+ " WHERE { "
				+ "?place gn:name ?name . "
				+ "?place gn:featureClass ?class . "
				+ "FILTER regex(STR(?name), '^"+placeName.replaceAll("'", "\\\\'")+"$', 'i') . "
				+ "FILTER(?class = gn:A || ?class = gn:P ) . " //A country, state, region..., P city, village,...
				+ "?place geo:lat ?lat . "
				+ "?place geo:long ?lon } limit 10";
		System.out.println(queryStr);
		Query query = QueryFactory.create(queryStr);
		//wait 10 seconds for every query
		try {
			Thread.sleep(10000);
		} catch (InterruptedException e) {
			e.printStackTrace();
		}

		// Remote execution.
		QueryExecution qexec = null;
		//default
		qexec = QueryExecutionFactory.sparqlService("http://www.lotico.com:3030/lotico/sparql", query);
		// Execute.
		ResultSet rs = qexec.execSelect();
		while (rs.hasNext()) {
			QuerySolution sol = rs.next();
			Map<String, String> place = new TreeMap<String, String>();
			place.put("name", sol.getLiteral("name").getLexicalForm());
			place.put("text", sol.getLiteral("name").getLexicalForm());
			place.put("lat", sol.getLiteral("lat").getLexicalForm());
			place.put("lon", sol.getLiteral("lon").getLexicalForm());
			place.put("uri", sol.get("place").toString());
			places.add(place);
		}
		qexec.close();
		System.out.println("count result set: "+places.size());
		return places;
	}	
	
	public static List<Map<String, String>> loadGeoCoordinatesFromLinkedGeodataEndPoint(String placeName) {

		List<Map<String, String>> places = new ArrayList<Map<String, String>>();
		String queryStr = "Prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> "
				+ "PREFIX ogc: <http://www.opengis.net/ont/geosparql#> "
				+ "PREFIX geom: <http://geovocab.org/geometry#> "
				+ "PREFIX lgdo: <http://linkedgeodata.org/ontology/> "
				+ "PREFIX lgdm: <http://linkedgeodata.org/meta/> "
				+ "PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#> "
				+ "SELECT distinct ?s ?l ?lat ?lon "
				+ "WHERE { ?s a lgdo:Place ; a lgdm:Node ; rdfs:label ?l ; geo:lat ?lat ; geo:long ?lon . "
				+ "FILTER (langMatches(lang(?l),'FR')) . " //TODO instead - within a given area
				+ "FILTER regex(STR(?l), '^"+placeName.replaceAll("'", "\\\\'")+"$', 'i') } limit 10";
		System.out.println(queryStr);
		Query query = QueryFactory.create(queryStr);
		//wait 10 seconds for every query
		try {
			Thread.sleep(10000);
		} catch (InterruptedException e) {
			e.printStackTrace();
		}

		// Remote execution.
		QueryExecution qexec = null;
		//default
		qexec = QueryExecutionFactory.sparqlService("http://linkedgeodata.org/sparql", query);
		// Execute.
		ResultSet rs = qexec.execSelect();
		while (rs.hasNext()) {
			QuerySolution sol = rs.next();
			Map<String, String> place = new TreeMap<String, String>();
			place.put("name", sol.getLiteral("l").getLexicalForm());
			place.put("text", sol.getLiteral("l").getLexicalForm());
			place.put("lat", sol.getLiteral("lat").getLexicalForm());
			place.put("lon", sol.getLiteral("lon").getLexicalForm());
			place.put("uri", sol.get("s").toString());
			places.add(place);
		}
		qexec.close();
		System.out.println("count result set: "+places.size());
		return places;
	}

	public static void main(String[] args) {
		//loadGeoCoordinatesFromGeosparqlEndPoint("Aix-en-provence");
		//loadGeoCoordinatesFromLinkedGeodataEndPoint("Aix-en-provence");
	}
}
