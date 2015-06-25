package fr.obvil.mercuregalant;

import java.io.FileNotFoundException;
import java.io.FileWriter;
import java.io.IOException;
import java.io.PrintWriter;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.Iterator;
import java.util.List;
import java.util.Map;
import java.util.TreeMap;
import java.util.Map.Entry;
import java.util.regex.Matcher;
import java.util.regex.Pattern;
import java.util.Set;

import org.apache.commons.lang3.StringUtils;
import org.apache.lucene.document.Document;
import org.apache.lucene.document.Field;
import org.apache.lucene.document.StringField;
import org.geojson.Feature;
import org.geojson.FeatureCollection;
import org.geojson.Point;

import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.hp.hpl.jena.datatypes.xsd.XSDDatatype;
import com.hp.hpl.jena.ontology.DatatypeProperty;
import com.hp.hpl.jena.ontology.Individual;
import com.hp.hpl.jena.ontology.OntClass;
import com.hp.hpl.jena.ontology.OntModel;
import com.hp.hpl.jena.ontology.OntModelSpec;
import com.hp.hpl.jena.query.Query;
import com.hp.hpl.jena.query.QueryExecution;
import com.hp.hpl.jena.query.QueryExecutionFactory;
import com.hp.hpl.jena.query.QueryFactory;
import com.hp.hpl.jena.query.QuerySolution;
import com.hp.hpl.jena.query.ResultSet;
import com.hp.hpl.jena.rdf.model.Literal;
import com.hp.hpl.jena.rdf.model.ModelFactory;
import com.hp.hpl.jena.rdf.model.Property;
import com.hp.hpl.jena.rdf.model.RDFNode;
import com.hp.hpl.jena.rdf.model.Resource;

/**
 * 
 * @author Carmen Brando (Labex OBVIL - Univ. Paris-Sorbonne - UPMC LIP6)
 * 
 *         Methods to extract geodata from the place taxonomy, Lat/Lon information 
 *         can sometimes be obtained from an URL of the Geonames Linked Data set.
 */
public class PlaceTaxonomyProcessing {

	public static Map<String, Map<String, String>> processPlaceTaxonomy(
			String source, String classNameOfIndividuals, String individualArtOntClass, String nSonto, String prefixObvil) {

		Map<String, Map<String, String>> geoInfoByPlaceNameID = new HashMap<String, Map<String, String>>();

		OntModel m = ModelFactory.createOntologyModel(OntModelSpec.OWL_MEM,
				null);
		// JENA doesn't support owl/xml, read rdf/xml
		m.read(source);
		
		//first, building index by place pointing to articles via the relation contains_place
		Map<String, List<Map<String,String>>> indexByPlace = new HashMap<String, List<Map<String,String>>>();
		for (Iterator<Individual> i = m.listIndividuals(); i.hasNext();) {
			Individual ind = i.next();
			// check individual belongs to the class Article
			Boolean is = false;
			Iterator<Resource> it = ind.listRDFTypes(true);
			while (it.hasNext() && !is) {
				Resource res = (Resource) it.next();
				OntClass ontCls = m.getOntClass(res.getURI());
				if (ontCls != null) {
					if (ontCls.getURI().split("#")[1]
							.equalsIgnoreCase(individualArtOntClass)) {
						is = true;
					}
				}
			}
			if (is) {
				Property prop = m.getProperty(nSonto+"#contains_place");
				int count = 0;
				List<RDFNode> l = ind.listPropertyValues(prop).toList();
				while (count < l.size()) {
					RDFNode propVal = l.get(count);
					String placeNamekey = replaceNonAlphabeticCharacters(propVal.toString().split("#")[1]);
					if (indexByPlace.get(placeNamekey) == null) {
						List<Map<String,String>> articles = new ArrayList<Map<String,String>>();
						Map<String, String> infoArticle = new HashMap<String, String>();
						infoArticle.put("articleOntoID", ind.getURI());
						String artid =  ind.getURI().split("#")[1];
						infoArticle.put("articleID", artid);
						infoArticle.put("articleURL", prefixObvil + artid);
						infoArticle.put("articleDate", artid.substring(3, 7));
						articles.add(infoArticle);
						indexByPlace.put(placeNamekey, articles);
					} else {
						List<Map<String,String>> articles = indexByPlace.get(placeNamekey);
						Map<String, String> infoArticle = new HashMap<String, String>();
						infoArticle.put("articleOntoID", ind.getURI());
						String artid =  ind.getURI().split("#")[1];
						infoArticle.put("articleID", artid);
						infoArticle.put("articleURL", prefixObvil + artid);
						infoArticle.put("articleDate", artid.substring(3, 6));
						articles.add(infoArticle);
					}
					count++;
				}
			}
		}
		
		/*//for every place, check individual belongs to the Place class
		// check data model information is defined in ontology
		Property geoLat = m.getProperty(nSonto + "#lat"); 
		Property geoLong = m.getProperty(nSonto + "#lon");
		
		int countNotFound = 0, countAmb = 0, countNonAmb = 0;
		for (Iterator<Individual> i = m.listIndividuals(); i.hasNext();) {
			Individual ind = i.next();
			Boolean is = false;
			Iterator<Resource> it = ind.listRDFTypes(true);
			while (it.hasNext() && !is) {
				Resource res = (Resource) it.next();
				OntClass ontCls = m.getOntClass(res.getURI());
				if (ontCls != null) {
					if (!ontCls.getURI().split("#")[1]
							.equalsIgnoreCase(classNameOfIndividuals)) {
						while (ontCls.getSuperClass() != null && !is) {
							ontCls = ontCls.getSuperClass();
							if (ontCls.getURI().split("#")[1]
									.equalsIgnoreCase(classNameOfIndividuals)) {
								is = true;
							}
						}
					} else {
						is = true;
					}
				}
			}
			//it is a place
			if (is) {
				String placeNameId = ind.getURI().split("#")[1];
				String placeName = placeNameId;
			*/	
				/*{"type":"Feature","properties":{"dbpediaUri":"http://dbpedia.org/page/Rome/", 
				 * "geoNamesUri":"http://sws.geonames.org/3169070/","level":"2","name":"Rome","articles":
				 * [["MG-1","http://obvil1","1672"], ["MG-2","http://obvil2","1673"]]},
				 * "geometry":{"type":"Point","coordinates":[12.51133,41.89193]}}*/
				
			/*	//obtaining data property lat/lon and article ids 				
				Map<String, String> map = new HashMap<String, String>();
					String[] placeDetails = r.split("\\|");
						map.put("name", StringUtils.capitalize(placeName.replace("_", " ").toLowerCase()).trim());
						map.put("lat", placeDetails[2]);
						map.put("lon", placeDetails[3]);
						map.put("geoNamesUri", "http://sws.geonames.org/"+placeDetails[1]+"/");
						//attention ne pas créer un point si il n'y a pas de documents TODO
						
						map.put("docId", lookForDocumentID(ind.getURI(), source, "Article")); //get from ontology doc ID
					geoInfoByPlaceNameID.put(placeNameId, map);
					//TODO write to ontology
					// TODO attention, si le lieu a déjà des coords, ne rien faire (utile pour la MAJ du fichier ontologie)
					
				}
			}
		

		System.out.println("CountNotFound: " + countNotFound
				+ " countNonAmbiguous: " + countNonAmb + " countAmbiguous: "
				+ countAmb);*/
		System.out.println("size of map: "+geoInfoByPlaceNameID.size());
		return geoInfoByPlaceNameID;
	}

	/**
	 * Remove special characters and spaces in mentions if necessary.
	 * 
	 * @param in
	 *            , the string
	 * @return the new string
	 */
	public static String replaceNonAlphabeticCharacters(String in) {
		Pattern p = Pattern.compile("\\s|'|-|_");
		Matcher m = p.matcher(in);
		String texteRemplace = m.replaceAll("");
		return texteRemplace.toLowerCase().trim();
	}
	
}
