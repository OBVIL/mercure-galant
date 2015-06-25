package fr.obvil.geoprocessing.enrichgeoonto.old;

import java.io.File;
import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.io.FileOutputStream;
import java.io.FileWriter;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.OutputStreamWriter;
import java.io.PrintWriter;
import java.io.UnsupportedEncodingException;
import java.util.Iterator;
import java.util.List;
import java.util.Map;
import java.util.Map.Entry;
import java.util.TreeMap;

import javax.xml.parsers.SAXParser;
import javax.xml.parsers.SAXParserFactory;

import org.xml.sax.Attributes;
import org.xml.sax.SAXException;
import org.xml.sax.helpers.DefaultHandler;
import org.geojson.Feature;
import org.geojson.FeatureCollection;
import org.geojson.Point;
import org.geonames.Toponym;
import org.xml.sax.helpers.DefaultHandler;

import au.com.bytecode.opencsv.CSVReader;
import au.com.bytecode.opencsv.CSVWriter;

import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.hp.hpl.jena.datatypes.xsd.XSDDatatype;
import com.hp.hpl.jena.ontology.DatatypeProperty;
import com.hp.hpl.jena.ontology.Individual;
import com.hp.hpl.jena.ontology.OntClass;
import com.hp.hpl.jena.ontology.OntModel;
import com.hp.hpl.jena.ontology.OntModelSpec;
import com.hp.hpl.jena.rdf.model.Literal;
import com.hp.hpl.jena.rdf.model.ModelFactory;
import com.hp.hpl.jena.rdf.model.Resource;
import com.hp.hpl.jena.rdf.model.Statement;
import com.hp.hpl.jena.util.FileManager;
import com.hp.hpl.jena.vocabulary.XSD;

public class OntologyEnrichment {

	

	public static Map<String, Map<String, String>> addGeoCoordToOntologyIndividuals(
			Map<String, Map<String, String>> toponyms, String source) {

		OntModel m = ModelFactory.createOntologyModel(OntModelSpec.OWL_MEM,
				null);
		m.read(source);
		// OntClass place = m.getOntClass( NS + "Place" );
		int arret = 0;
		for (Iterator<Individual> i = m.listIndividuals(); i.hasNext()
				&& arret < 25;) {
			Individual ind = i.next();
			String placeName = ind.getURI().split("#")[1].replaceAll("_", " ");
			if (ind.getURI().split("#")[1].contains(",")) {
				// String parent =
				// ind.getOntClass().getSuperClass().getURI().split("#")[1].replaceAll("_",
				// " ");

			} else {
				// only if not already in toponyms
				boolean in = false;
				for (String uri : toponyms.keySet()) {
					if (uri.split("#")[1].equals(placeName))
						in = true;
				}
				if (!in) {
					List<Map<String, String>> result = GeolocalisePlace
							.loadGeoCoordinatesFromGeosparqlEndPoint(placeName);
					int candidateN = 0;
					for (Map<String, String> place : result) {
						toponyms.put(ind.getURI() + "#" + candidateN, place);
						candidateN++;
					}
				}
			}
			arret++;// TODO
		}
		System.out.println("nb of geolocalized places: " + toponyms.size());
		return toponyms;
	}

	public static void enrichOntology(
			Map<String, Map<String, String>> toponyms, String source,
			String output) {

		String NS_geo = "http://www.w3.org/2003/01/geo/wgs84_pos#";
		OntModel m = ModelFactory.createOntologyModel(OntModelSpec.OWL_MEM,
				null);
		InputStream ontInputStream = FileManager.get().open(source);
		m.read(ontInputStream, "RDF/XML");
		DatatypeProperty hasGeoLat = m.getDatatypeProperty(NS_geo + "lat");
		DatatypeProperty hasGeoLong = m.getDatatypeProperty(NS_geo + "long");

		for (Iterator<Individual> i = m.listIndividuals(); i.hasNext();) {
			Individual ind = i.next();
			if (!ind.getURI().contains("[")) {
				if (toponyms.get(ind.getURI() + "#0") != null) {
					Literal lat = m.createTypedLiteral(
							toponyms.get(ind.getURI() + "#0").get("lat"),
							XSDDatatype.XSDdouble);
					Literal lon = m.createTypedLiteral(
							toponyms.get(ind.getURI() + "#0").get("lon"),
							XSDDatatype.XSDdouble);
					ind.addProperty(hasGeoLat, lat);
					ind.addProperty(hasGeoLong, lon);
				}
			}
		}
		FileWriter out = null;
		try {
			out = new FileWriter(output);
			m.setStrictMode(false);
			m.write(out, "RDF/XML");
		} catch (IOException ignore) {
			System.out.println("problem creating new ontology");
		}
	}

	public static void enrichOntologyWithSax(
			Map<String, Map<String, String>> toponyms, String source,
			String output) {

		try {
			SAXParserFactory factory = SAXParserFactory.newInstance();
			SAXParser saxParser = factory.newSAXParser();
			DefaultHandler handler = new DefaultHandler() {

				boolean bfname = false;

				public void startElement(String uri, String localName,
						String qName, Attributes attributes)
								throws SAXException {

					System.out.println("Start Element :" + qName);

					if (qName.equalsIgnoreCase("FIRSTNAME")) {
						bfname = true;
					}
				}

				public void endElement(String uri, String localName,
						String qName) throws SAXException {

					System.out.println("End Element :" + qName);

				}

				public void characters(char ch[], int start, int length)
						throws SAXException {

					if (bfname) {
						System.out.println("First Name : "
								+ new String(ch, start, length));
						bfname = false;
					}
				}
			};
			saxParser.parse(source, handler);
		} catch (Exception e) {
			e.printStackTrace();
		}
	}

	public static void writeGeoMap(Map<String, Map<String, String>> geoMap,
			String out) {
		try {
			CSVWriter writer = new CSVWriter(new OutputStreamWriter(
					new FileOutputStream(out), "UTF-8"), '\t',
					CSVWriter.NO_QUOTE_CHARACTER);
			for (String k : geoMap.keySet()) {
				String[] n = { k, geoMap.get(k).get("name"),
						geoMap.get(k).get("text"), geoMap.get(k).get("lat"),
						geoMap.get(k).get("lon"), geoMap.get(k).get("uri") };
				writer.writeNext(n);
			}
			writer.close();
		} catch (UnsupportedEncodingException e) {
			e.printStackTrace();
		} catch (FileNotFoundException e) {
			e.printStackTrace();
		} catch (IOException e) {
			e.printStackTrace();
		}
	}

	public static Map<String, Map<String, String>> loadGeoMap(String outfile) {
		Map<String, Map<String, String>> geoMap = new TreeMap<String, Map<String, String>>();
		try {
			if (new File(outfile).exists()) {
				CSVReader reader = new CSVReader(new InputStreamReader(
						new FileInputStream(outfile), "UTF-8"), '\t',
						CSVWriter.NO_QUOTE_CHARACTER);
				String[] line = {};
				while ((line = reader.readNext()) != null) {
					Map<String, String> entry = new TreeMap<String, String>();
					if (line.length > 4) {
						entry.put("name", line[1]);
						entry.put("text", line[2]);
						entry.put("lat", line[3]);
						entry.put("lon", line[4]);
						entry.put("uri", line[5]);
					}
					geoMap.put(line[0], entry);
				}
				for (String k : geoMap.keySet()) {
					String[] n = new String[geoMap.get(k).size()];
					int c = 0;
					for (String l : geoMap.get(k).keySet()) {
						n[c] = geoMap.get(k).get(l);
						c++;
					}

				}
				reader.close();
			}
		} catch (UnsupportedEncodingException e) {
			e.printStackTrace();
		} catch (FileNotFoundException e) {
			e.printStackTrace();
		} catch (IOException e) {
			e.printStackTrace();
		}
		return geoMap;
	}

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
			feat.setProperty("date", "1914"); // TODO temporary
			featureCollection.add(feat);
		}

		try {
			PrintWriter out = new PrintWriter(outgeojson);
			String json = new ObjectMapper()
			.writeValueAsString(featureCollection);
			out.println(json);
			out.close();
		} catch (JsonProcessingException e) {
			e.printStackTrace();
		} catch (FileNotFoundException e) {
			e.printStackTrace();
		}
	}

}
